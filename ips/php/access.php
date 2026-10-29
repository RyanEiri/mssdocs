<?php
/* Who may read something that has a status and an access level: the one place a later subscription system replaces (issues and, later, articles use it).
 *   $viewer  null for an anonymous visitor, else the signed-in user's name
 *   $status  draft, published or private (D9 of docs/mssdocs-metadata-mapping.md)
 *   $access  open (anyone, once published) or signed-in (any signed-in user, once published)
 * A draft or private item is for signed-in editors only; a published one follows its access level. An unknown value is read as the most restrictive. */
function ips_access_can_read($viewer, $status, $access) {
	$signed_in = is_string($viewer) && $viewer !== '';
	if ($status !== 'published') {
		return $signed_in;
	}
	return $access === 'open' ? true : $signed_in;
}
