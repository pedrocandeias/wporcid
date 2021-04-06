=== Wordpress ORCID importer ===

Contributors: Pedro Candeias
Tags: ORCID, Shortcodes, JSON
Requires at least: 5.4
Tested up to: 5.6
Requires PHP: 5.6
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl

== Description ==

Wordpress ORCID importer plugin allows to import data from the ORCID API.
It works using Shortcodes and the Author ID from ORCID.

== How to use it ==

Just paste the shortcode [wp-orcid-importer orcidid="ORCID-AUTHOR-ID-HERE" publimit="NUMBER OF PUBLICATIONS TO SHOW"]
You can show the works for more than one person at a time, using a comma to separate the ORCID ID:

[wp-orcid-importer orcidid="0000-0003-3264-5519,0000-0003-3264-55120" publimit="3"]