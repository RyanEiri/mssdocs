<?php
/* The artefact of a document: the object an XML or HTML file describes, shown beside it in the editor as page images or a PDF kept in the file store.
 * ips_fe_artefact() finds it, trying in this order and stopping at the first that has something:
 *   1. the Projects module: the document is linked to a project, and the project's files with the role "artefact-page" (images, in the project's order, each with its label,
 *      for example "fol. 12r") or "artefact-pdf". A document in several projects gives one source for each (the pane offers a switcher);
 *   2. the document's own TEI: <graphic url> (inside a <surface n>) and <pb n facs>, for an address that names a file inside the file store (a relative path from the document,
 *      or a full address with /upload/files/ in it); an address anywhere else is ignored;
 *   3. a folder called "<xml:id>-pages" anywhere in the file store (depth 4) that holds page images, the xml:id being the <msDesc>'s (or the file's name);
 *   4. a PDF called "<xml:id>.pdf" in the same places.
 * A source is {id, kind, title, pages: [{url, label, name}], pdf: {url, name} | null}. Nothing here needs an XML extension: the TEI is read with regular expressions, since
 * not every host has dom (vesturheimsrit's PHP does not). Every file is checked to be a real file inside upload/files/ (ips_fe_artefact_resolve: links out of it, hidden
 * names and the thumbnail folders are refused), and the pane only asks for files a signed-in user can already read. Only the first tier needs the Projects module.
 */

const IPS_FE_ARTEFACT_IMAGES = '/\.(jpe?g|png|gif|webp)$/i';
const IPS_FE_ARTEFACT_MAX_PAGES = 2000;
const IPS_FE_ARTEFACT_MAX_PROJECTS = 20;
const IPS_FE_ARTEFACT_SEARCH_DEPTH = 4;
const IPS_FE_ARTEFACT_SEARCH_LIMIT = 6000; // directory entries looked at in one search

// The address a browser uses for a file of the store: "A/b c.jpg" -> USER_FILES_URL . "A/b%20c.jpg".
function ips_fe_artefact_url($rel) {
	return USER_FILES_URL . implode('/', array_map('rawurlencode', explode('/', $rel)));
}

// The real path of a store-relative name ("A/b.jpg") when it is a file inside upload/files/, else null: no empty, hidden or thumbnail-folder segment, and a link
// out of the store is refused (realpath).
function ips_fe_artefact_resolve($rel) {
	if (!is_string($rel) || $rel === '' || strpbrk($rel, "\\\0") !== false) {
		return null;
	}
	foreach (explode('/', $rel) as $segment) {
		if ($segment === '' || $segment[0] === '.' || preg_match(IPS_VERSION_DIR, $segment)) {
			return null;
		}
	}
	$root = ips_files_root();
	$real = $root === false ? false : realpath($root . '/' . $rel);
	return ($real !== false && is_file($real) && strpos($real, $root . '/') === 0) ? $real : null;
}

// A page of a source from a store-relative name, or null when it is not a real file the pane may show.
function ips_fe_artefact_page($rel, $label) {
	return ips_fe_artefact_resolve($rel) === null ? null : ['url' => ips_fe_artefact_url($rel), 'label' => (string) $label, 'name' => basename($rel)];
}

// ---- 1. the Projects module ---------------------------------------------------------------------------------------------------------------

function ips_fe_artefact_from_projects($store_rel) {
	if (!function_exists('ips_projects_ready') || !ips_projects_ready()) {
		return [];
	}
	$real = ips_fe_artefact_resolve($store_rel);
	$file_id = $real === null ? null : ips_projects_file_id(['rel' => $store_rel, 'real' => $real], false);
	if (!$file_id) {
		return [];
	}
	$sources = [];
	foreach (array_slice(ips_projects_of_file($file_id), 0, IPS_FE_ARTEFACT_MAX_PROJECTS) as $project) {
		if ($project['status'] === 'archived') {
			continue;
		}
		$pages = [];
		$pdf = null;
		foreach (ips_projects_files($project['project_id']) as $link) {
			if ($link['missing']) {
				continue;
			}
			if ($link['role'] === 'artefact-page' && count($pages) < IPS_FE_ARTEFACT_MAX_PAGES) {
				$page = ips_fe_artefact_page(substr($link['path'], 6), $link['label']);
				if ($page !== null) {
					$pages[] = $page;
				}
			} elseif ($link['role'] === 'artefact-pdf' && $pdf === null && preg_match('/\.pdf$/i', $link['name'])) {
				$page = ips_fe_artefact_page(substr($link['path'], 6), '');
				$pdf = $page === null ? null : ['url' => $page['url'], 'name' => $page['name']];
			}
		}
		if ($pages || $pdf) {
			$sources[] = ['id' => 'project:' . $project['project_id'], 'kind' => 'project', 'title' => $project['title'], 'pages' => $pages, 'pdf' => $pdf];
		}
	}
	return $sources;
}

// ---- 2. the document's own TEI -----------------------------------------------------------------------------------------------------------

// The id the document goes by: the <msDesc>'s xml:id, else the root's, else the file's name without its extension.
function ips_fe_artefact_id($content, $real) {
	if (preg_match('/<msDesc\b[^>]*\sxml:id\s*=\s*"([^"]+)"/', $content, $m) || preg_match('/<TEI\b[^>]*\sxml:id\s*=\s*"([^"]+)"/', $content, $m)) {
		return $m[1];
	}
	return pathinfo($real, PATHINFO_FILENAME);
}

// A store-relative name ("A/b.jpg") for an address a TEI file gives, or null when it does not name a file inside the store.
function ips_fe_artefact_rel($address, $doc_dir) {
	$address = html_entity_decode(trim($address), ENT_QUOTES, 'UTF-8');
	if ($address === '' || $address[0] === '#' || strpbrk($address, "\0\\") !== false) {
		return null;
	}
	if (preg_match('~^[a-z][a-z0-9+.-]*://~i', $address)) {
		$path = parse_url($address, PHP_URL_PATH);
		$at = is_string($path) ? strpos($path, '/upload/files/') : false;
		return $at === false ? null : rawurldecode(substr($path, $at + strlen('/upload/files/')));
	}
	$real = realpath($doc_dir . '/' . rawurldecode(preg_replace('/[?#].*$/', '', $address)));
	$root = ips_files_root();
	return ($real !== false && $root !== false && strpos($real, $root . '/') === 0) ? substr($real, strlen($root) + 1) : null;
}

function ips_fe_artefact_from_tei($content, $real) {
	$pages = [];
	$label = '';
	preg_match_all('~<(surface|graphic|pb)\b([^>]*?)/?>~s', $content, $tags, PREG_SET_ORDER);
	foreach ($tags as $tag) {
		$attr = function ($name) use ($tag) {
			return preg_match('/\s' . $name . '\s*=\s*"([^"]*)"/', $tag[2], $m) ? $m[1] : '';
		};
		if ($tag[1] === 'surface') {
			$label = $attr('n');
			continue;
		}
		$address = $tag[1] === 'graphic' ? $attr('url') : $attr('facs');
		$rel = $address === '' ? null : ips_fe_artefact_rel($address, dirname($real));
		if ($rel === null || !preg_match(IPS_FE_ARTEFACT_IMAGES, $rel) || count($pages) >= IPS_FE_ARTEFACT_MAX_PAGES) {
			continue;
		}
		$page = ips_fe_artefact_page($rel, $tag[1] === 'pb' ? $attr('n') : $label);
		if ($page !== null) {
			$pages[] = $page;
		}
	}
	return $pages ? [['id' => 'tei', 'kind' => 'tei', 'title' => 'Facsimile in the TEI', 'pages' => $pages, 'pdf' => null]] : [];
}

// ---- 3 and 4. a folder or a PDF named for the document -----------------------------------------------------------------------------------

// Looks through the file store (to a depth, never into hidden or thumbnail folders, never through a link out of it) for a folder or file called $name (any case).
// Returns the store-relative name of the first one found, or null. The number of entries looked at is bounded.
function ips_fe_artefact_find($name, $want_dir) {
	$root = ips_files_root();
	if ($root === false) {
		return null;
	}
	$looked = 0;
	$walk = function ($dir, $depth) use (&$walk, $name, $want_dir, $root, &$looked) {
		$entries = @scandir($dir);
		if ($entries === false) {
			return null;
		}
		$below = [];
		foreach ($entries as $entry) {
			if ($entry === '.' || $entry === '..' || $entry[0] === '.' || preg_match(IPS_VERSION_DIR, $entry) || ++$looked > IPS_FE_ARTEFACT_SEARCH_LIMIT) {
				continue;
			}
			$path = $dir . '/' . $entry;
			if (is_link($path) && strpos((string) realpath($path), $root . '/') !== 0) {
				continue;
			}
			$is_dir = is_dir($path);
			if (strcasecmp($entry, $name) === 0 && $is_dir === $want_dir) {
				return substr($path, strlen($root) + 1);
			}
			if ($is_dir && $depth < IPS_FE_ARTEFACT_SEARCH_DEPTH) {
				$below[] = $path;
			}
		}
		foreach ($below as $path) {
			$found = $walk($path, $depth + 1);
			if ($found !== null) {
				return $found;
			}
		}
		return null;
	};
	return $walk($root, 1);
}

function ips_fe_artefact_from_folder($id) {
	$rel = ips_fe_artefact_find($id . '-pages', true);
	$root = ips_files_root();
	if ($rel === null || $root === false) {
		return [];
	}
	$names = array_values(array_filter(@scandir($root . '/' . $rel) ?: [], function ($n) { return $n[0] !== '.' && preg_match(IPS_FE_ARTEFACT_IMAGES, $n); }));
	usort($names, 'strnatcasecmp');
	$pages = [];
	foreach (array_slice($names, 0, IPS_FE_ARTEFACT_MAX_PAGES) as $name) {
		$page = ips_fe_artefact_page($rel . '/' . $name, '');
		if ($page !== null) {
			$pages[] = $page;
		}
	}
	return $pages ? [['id' => 'folder', 'kind' => 'folder', 'title' => $id . '-pages', 'pages' => $pages, 'pdf' => null]] : [];
}

function ips_fe_artefact_from_pdf($id) {
	$rel = ips_fe_artefact_find($id . '.pdf', false);
	$page = $rel === null ? null : ips_fe_artefact_page($rel, '');
	return $page === null ? [] : [['id' => 'pdf', 'kind' => 'pdf', 'title' => $page['name'], 'pages' => [], 'pdf' => ['url' => $page['url'], 'name' => $page['name']]]];
}

// ---- all of it ---------------------------------------------------------------------------------------------------------------------------

// The sources for the document at $real (its text in $content), the best first, and a hint for the page when there are none.
function ips_fe_artefact($real, $content, $kind) {
	$sources = ips_fe_artefact_from_projects(substr(ips_fe_name($real), strlen('upload/files/')));
	if (!$sources && $kind === 'xml') {
		$sources = ips_fe_artefact_from_tei($content, $real);
	}
	if (!$sources) {
		$ids = array_values(array_unique([ips_fe_artefact_id($content, $real), pathinfo($real, PATHINFO_FILENAME)]));
		foreach ($ids as $id) {
			$sources = $sources ?: ips_fe_artefact_from_folder($id);
		}
		foreach ($ids as $id) {
			$sources = $sources ?: ips_fe_artefact_from_pdf($id);
		}
	}
	return ['sources' => $sources, 'hint' => 'No artefact is linked to this document. In the Files page, put the document and its page images (or a PDF) in one project, with the images'
		. ' as "Artefact page" and the PDF as "Artefact PDF"; or name a folder <id>-pages, or a PDF <id>.pdf, after the document\'s xml:id.'];
}
