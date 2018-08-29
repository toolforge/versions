<!DOCTYPE html>
<!--
Copyright (c) 2015 Bryan Davis <bd808@wikimedia.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
http://www.gnu.org/copyleft/gpl.html
-->
<html lang="en">
<head>
<meta charset="utf-8">
<title>Wikimedia MediaWiki versions</title>
<meta name="viewport" content="initial-scale=1.0, user-scalable=yes, width=device-width">
<style>
/* http://meyerweb.com/eric/tools/css/reset/
   v2.0 | 20110126
   License: none (public domain)
*/
html, body, div, span, applet, object, iframe, h1, h2, h3, h4, h5, h6, p, blockquote, pre, a, abbr, acronym, address, big, cite, code, del, dfn, em, img, ins, kbd, q, s, samp, small, strike, strong, sub, sup, tt, var, b, u, i, center, dl, dt, dd, ol, ul, li, fieldset, form, label, legend, table, caption, tbody, tfoot, thead, tr, th, td, article, aside, canvas, details, embed, figure, figcaption, footer, header, hgroup, menu, nav, output, ruby, section, summary, time, mark, audio, video {margin:0;padding:0;border:0;font-size:100%;font:inherit;vertical-align:baseline;}
article, aside, details, figcaption, figure, footer, header, hgroup, menu, nav, section {display:block;}
body {line-height:1;}
ol, ul {list-style:none;}
blockquote, q {quotes:none;}
blockquote:before, blockquote:after, q:before, q:after {content:'';content:none;}
</style>
<style>
body {background:#fefefe;color:#333;padding:.5em;}
h1 {color:#484848;font-size:2em;padding:.5em;text-align:center;}
#groups {overflow:auto;}
.group {border:2px solid #666;margin-bottom:1em;}
.group h2 {color:#fefefe;font-size:2em;padding:.5em;text-align:center;}
.group-0 h2 {background:#900}
.group-1 h2 {background:#069}
.group-2 h2 {background:#396}
.version label {cursor:pointer;display:block;font-size:1.5em;padding:.5em;text-align:center;}
.version label:after {font-size:.5em;font-weight:normal;font-style:normal;display:inline-block;text-decoration:inherit;padding-left:1ch;}
.version > input ~ ul {display:none;}
.version > input ~ label:after {content:"▶"}
.version > input:checked ~ label:after {content:"▼"}
.version > input:checked ~ ul {display:block;}
.version > input {display:none;}
.wikis {background:#f0f0f0;max-height:300px;overflow:scroll;padding:.5em;-webkit-columns:8em;-moz-columns:8em;columns:8em;}
.wikis li {overflow:hidden;text-overflow:ellipsis;white-space:nowrap;width:8em;}
.wikis li:hover {overflow:visible;}
.wikis li a {color:#333;position:relative;text-decoration:none;}
.wikis li a:hover:after {background:#f0f0f0;bottom:0;content:attr(id);left:0;padding-right:.5em;position:absolute;z-index:98;}
#links, #sal {padding:.5em}
.sal {border:1px solid #ccc;padding:.5em;}
.sal span {display:inline-block;}
.sal .day, .sal .time, .sal.nick {white-space:nowrap;}
.nick {color:#666;}
.log {padding-left:1em;}
#about {margin-top:2em;}
footer {clear:both;margin-top:2em;padding-top:1em;border-top:1px solid #333;text-align:right;}
#powered-by {float:left;}
@media only screen and (min-width: 768px) {
  body {padding:2em;}
  .group {width:30%;margin:0 1%;float:left;}
  #links li {display:inline-block;width:33%;text-align:center;}
  .sal {display:table;}
  .sal li {display:table-row;}
  .sal li span {display:table-cell;padding:.1em;vertical-align:top;}
}
</style>
</head>
<body>
<header>
<h1>Wikimedia MediaWiki versions</h1>
</header>
<section id="groups">
<?php
/**
 * Get a config file from noc.wikimedia.org
 *
 * @param string $file Filename
 * @return string File contents
 */
function confFile( $file ) {
    // Add a cache-busting parameter (T202734)
    $time = time();
    return file_get_contents( "https://noc.wikimedia.org/conf/{$file}?t={$time}" );
}

/**
 * Get an array of database names from a .dblist file
 *
 * @param string $list
 * @return array
 */
function dbList( $list ) {
    $list = basename( $list, '.dblist' );
    $lines = explode( "\n", confFile( "dblists/{$list}.dblist" ) );
    $dbs = array();
    foreach ( $lines as $line ) {
        $line = trim( substr( $line, 0, strcspn( $line, '#' ) ) );
        if ( substr( $line, 0, 2 ) === '%%' ) {
            $dbs = evalDbList( $line );
            break;
        } elseif ( $line !== '' ) {
            $dbs[] = $line;
        }
    }
    return $dbs;
}

/**
 * Evaluate a dblist construction expression
 *
 * @param string $expr
 * @return array
 */
function evalDbList( $expr ) {
    $expr = trim( strtok( $expr, "#\n" ), '% ' );
    $term = strtok( $expr, ' ' );
    $result = dbList( $term );
    while ( $op = strtok( ' ' ) ) {
        $part = dbList( strtok( ' ' ) );
        if ( $op === '+' ) {
            $result = array_unique( array_merge( $result, $part ) );
        } elseif ( $op === '-' ) {
            $result = array_diff( $result, $part );
        }
    }
    sort( $result );
    return $result;
}

/**
 * Escape a string using htmlspecialchars.
 *
 * @param string $str
 * @return string
 */
function hsc( $str ) {
    return htmlspecialchars( $str, ENT_QUOTES | ENT_HTML5, 'UTF-8', false );
}

/**
 * @param array $arr Source array
 * @param array $keys List of keys to select
 * @return array
 */
function valuesForKeys( $arr, $keys ) {
	$ret = [];
	foreach( $keys as $key ) {
		if ( array_key_exists( $key, $arr ) ) {
			$ret[$key] = $arr[$key];
		}
	}
	return $ret;
}

/**
 * @param array $versions
 * @param array $wikis
 * @return array
 */
function versions( $versions, $wikis ) {
	return array_unique( valuesForKeys( $versions, $wikis ) );
}

/**
 * Render HTML for a wiki group.
 *
 * @param string $label Group label (e.g. "Group 0")
 * @param array $wikis
 * @param string $version MediaWiki versions
 */
function showGroup( $label, $wikis, $versions ) {
    $id = strtolower( preg_replace( '/\W/', '-', $label ) );
?>
<div class="group <?= hsc( $id ) ?>">
<h2><?= hsc( $label ) ?></h2>
<div class="version">
<input type="checkbox" id="<?= hsc( $id ) ?>">
<label for="<?= hsc( $id ) ?>"><?= hsc( implode( ' / ', $versions ) ) ?></label>
<ul class="wikis">
<?php foreach( $wikis as $wiki ) { ?>
<li><a id="<?= hsc( $wiki ) ?>"><?= hsc( $wiki ) ?></a></li>
<?php } ?>
</ul>
</div>
</div>
<?php
} // end showGroup

/**
 * Fetch the last 10 SAL messages.
 *
 * @return array
 */
function getSal() {
    $resp = json_decode( file_get_contents( 'http://tools-elastic-03/sal/sal/_search?q=project:production&_source_include=@timestamp,project,nick,message&sort=@timestamp:desc' ) );
    return $resp->hits->hits;
}

$wikiVersions = json_decode( confFile( 'wikiversions.json' ), true );
array_walk( $wikiVersions, function ( &$ver ) {
    $ver = substr( $ver, 4 );
} );
$group0 = dbList( 'group0' );
$group1 = dbList( 'group1' );
$group2 = array_values( array_diff( array_keys( $wikiVersions ), $group0, $group1 ) );

showGroup( 'Group 0', $group0, versions( $wikiVersions, $group0 ) );
showGroup( 'Group 1', $group1, versions( $wikiVersions, $group1 ) );
showGroup( 'Group 2', $group2, versions( $wikiVersions, $group2 ) );
?>
</section>
<section id="sal">
<ul class="sal">
<?php
foreach ( getSal() as $hit ) {
    $date = new DateTime( $hit->_source->{'@timestamp'} );
?>
<li>
<span class="day"><?= $date->format( 'Y-m-d' ); ?></span>
<span class="time"><?= $date->format( 'H:i' ); ?></span>
<span class="nick">&lt;<?= hsc( $hit->_source->nick ); ?>&gt;</span>
<span class="log"><?= hsc( $hit->_source->message ); ?></span>
</li>
<?php
}
?>
</ul>
</section>
<section id="links">
<ul>
<li><a href="https://wikitech.wikimedia.org/wiki/Deployments#Near-term">Deployments calendar</a></li>
<li><a href="https://www.mediawiki.org/wiki/Deployment_Train">Roadmap</a></li>
<li><a href="https://tools.wmflabs.org/sal/production">Server Admin Log</a></li>
</ul>
</section>
<section>
<p id="about">Version information read from live configuration files hosted on <a href="https://noc.wikimedia.org/conf/">noc.wikimedia.org</a>.</p>
</section>
<footer>
<div id="powered-by">
<a href="/"><img src="https://tools-static.wmflabs.org/toolforge/banners/Powered-by-Toolforge.png" alt="Powered by Wikimedia Toolforge"></a>
</div>
<a id="source" href="https://phabricator.wikimedia.org/source/tool-versions/">view source</a>
</footer>
</body>
</html>
