# schemas/

Where the file editor's XML validator (`../schema.php`, `../validate.php`) looks for the Menota schema. **The schema files are not in this repository**: menota.org states no licence for them.
`tools/fetch-menota-schema.sh <folder>` downloads the current ones (`menotaP5.rng`, the ODD `menotaP5.xml`, `menota-entities.txt`) with a `SOURCE.txt` giving their source, date and SHA-256; keep the result in a folder
outside the repository (the fleet's is `/mnt/www/_vendor/menota-3.0/` on the file share) and put `menotaP5.rng` and `menota-entities.txt` here on each site (the dev stacks mount that folder over this one).

The editor shows its **Validate** button only when this folder holds `menotaP5.rng` **and** the site's `php/site-config.php` names an interpreter with `define('IPS_FE_PYTHON', '/path/to/python3');`, a Python whose lxml is built on libxml2 **2.15 or newer** (`../validate_xml.py` refuses an older one: compiling the Menota schema takes minutes below 2.15, and nothing the fleet's Debian 12 hosts can install has 2.15 yet). A site may also point `IPS_FE_SCHEMA_FILE` at another RELAX NG file (`menota-entities.txt` is read from the same folder), and set `IPS_FE_VALIDATE_TIMEOUT` and `IPS_FE_VALIDATE_SLOTS`.
