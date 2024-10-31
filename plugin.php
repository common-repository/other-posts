<?php

/*
  Plugin Name: Other Posts
  Plugin URI: http://www.satollo.net/plugins/other-posts
  Description: Show posts related with the current displayed. You can design your own list layout using simple tags.
  Version: 1.3.0
  Author: Stefano Lissa
  Author URI: http://www.satollo.net
 */

/* 	Copyright 2014  Stefano Lissa  (email : satollo@gmail.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

function oposts_find_posts($post_id = null, $max = 0) {

    global $wpdb, $post;

    if ($post_id)
        $apost = get_post($post_id);
    else
        $apost = &$post;

    $options = get_option('oposts');
    if (!is_numeric($max))
        $max = 4;
    $terms = preg_replace('/[^a-z0-9]/i', ' ', $apost->post_title);
    $terms2 = preg_replace('/[^a-z0-9]/i', ' ', strip_tags($apost->post_content));
    if (strlen($terms2) > 1000) {
        $x = strpos($terms2, ' ', 1000);
        if ($x > 0)
            $terms2 = substr($terms2, 0, $x);
    }
    $now = gmdate("Y-m-d H:i:s", time() + get_option('gmt_offset') * 3600);
    
    $terms .= ' ' . $terms2;

    $query = "select id, post_content, post_title, match(post_title, post_content) against (%s) as score from " . $wpdb->posts .
            " where match(post_title, post_content) against (%s)" . //" and post_date<='" . $now . "'" .
            " and post_type='post' and post_status='publish' and id<>" . $apost->ID .
            " order by score desc limit " . $max;

    return $wpdb->get_results($wpdb->prepare($query, $terms, $terms));
}

/**
 * Return a $max number of posts searched within the published posts versus the
 * $terms string, which can be a number of words space separated. If $future
 * is true, even the programmed posts will be returned.
 */
function oposts_search($terms, $max, $future = false) {

    global $wpdb;

    $options = get_option('oposts');
    $now = gmdate("Y-m-d H:i:s", time() + get_option('gmt_offset') * 3600);

    $terms = preg_replace('/[^a-z0-9]/i', ' ', $terms);

    $query = "select id, post_content, post_title, match(post_title, post_content) against ('" .
            $terms . "') as score from " . $wpdb->posts .
            " where match(post_title, post_content) against ('" .
            $terms . "')";

    if (!$future)
        $query .= " and post_date<='" . $now . "'";

    $query .= " and post_stype=\'post\' and post_status in ('publish', 'future')" .
            " order by score desc limit " . $max;

    return $wpdb->get_results($query);
}

function oposts_extract_image($post_id) {
    global $oposts_options;

    $id = function_exists('get_post_thumbnail_id') ? get_post_thumbnail_id($post_id) : false;
    if ($id) {
        $image = wp_get_attachment_image_src($id, 'thumbnail');
        return $image[0];
    }

    $attachments = get_children(array(
        'post_parent' => $post_id,
        'post_status' => 'inherit',
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'order' => 'ASC',
        'orderby' => 'menu_order ID'
            )
    );

    if (empty($attachments)) {
        if (isset($oposts_options['useimg'])) {
            $post = get_post($post_id);

            $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches);
            if (!empty($matches[1][0])) {
                return $matches[1][0];
            }
        }

        if (empty($oposts_options['thumb']))
            return get_option('siteurl') . '/wp-content/plugins/other-posts/images/empty.gif';
        else
            return $oposts_options['thumb'];
    }

    foreach ($attachments as $id => $attachment) {
        $image = wp_get_attachment_image_src($id, 'thumbnail');
        return $image[0];
    }
}

add_action('wp_head', 'opost_wp_head');
function opost_wp_head() {
    $options = get_option('oposts');
    
 if ($options['template'] == 4) {
     ?>
<style>
.opost-item {
    margin-bottom: 15px;
}
.opost-title a {
    font-weight: bold;
    text-decoration: none;
}
.opost-image {
    float: left;
    margin: 0 15px 0 0;
    background-color: #eee;
}
.opost-image img {
    border: 0;
    width: 75px;
    height: 75px;
}
@media (max-width: 767px) {
    .opost-title a {
        font-weight: normal;
    }
    .opost-excerpt {
        display: none;
    }
    .opost-image img {
        width: 40px;
        height: 40px;
    }
}
</style>
<?php
 }
}

/**
 * Outputs the HTML code with the related posts list.
 */
function oposts_show($echo = true) {
    global $wpdb, $post, $oposts_options;

    $options = get_option('oposts');
    $results = oposts_find_posts(null, $options['max']);
    if (!$results)
        return;

    if ($options['template'] == 1) {
        $options['header'] .= '<table class="oposts">';
        $options['body'] = '<tr>
<td valign="top" style="margin: 0; padding: 5px; vertical-align: top"><img src="{image}" width="50"/></td>
<td valign="top" style="margin: 0; padding: 5px; vertical-align: top" align="left"><a href="{link}">{title}</a><br /><small>{excerpt}</small></td>
</tr>';
        $options['footer'] = '</table>' . $options['footer'];
    }

    if ($options['template'] == 2) {
        $options['header'] .= '<table class="oposts"><tr>';
        $options['body'] = '
<td valign="top" style="margin: 0; padding: 5px; vertical-align: top; text-align: center"><img src="{image}" width="75"/><br />
<a href="{link}">{title}</a><br /><small>{excerpt}</small></td>';
        $options['footer'] = '</tr></table>' . $options['footer'];
    }

    if ($options['template'] == 3) {
        $options['header'] .= '<ul>';
        $options['body'] = '<li><a href="{link}">{title}</a></li>';
        $options['footer'] = '</ul>' . $options['footer'];
    }
    
    // Horizontal list responsive
 if ($options['template'] == 4) {
        $options['header'] .= '<div class="opost-horizontal">';
        $options['body'] = '<div class="opost-item">'
                . '<div class="opost-image"><a href="{link}"><img src="{image}"></a></div>'
                . '<div class="opost-title"><a href="{link}">{title}</a></div>'
                . '<div class="opost-excerpt">{excerpt}</div>'
                . '<div style="clear:both"></div>'
                . '</div>';
        $options['footer'] = '</div>';
    }    

    $buffer = $options['header'];
    $c = count($results);
    for ($i = 0; $i < $c; $i++) {
        $r = &$results[$i];
        //$p = get_post($r->id);
        $t = get_the_title($r->id);
        $l = get_permalink($r->id);

        $content = $r->post_content;
        // Remove the short codes
        $content = preg_replace('/\[.*\]/', '', $content);

        $image = oposts_extract_image($r->id);

        // Excerpt extraction
        $excerpt_length = $oposts_options['excerpt'];
        if (!empty($excerpt_length) && is_numeric($excerpt_length)) {
            $excerpt = strip_tags($content);
            if (strlen($excerpt) > $excerpt_length) {
                $x = strpos($excerpt, ' ', $excerpt_length);
                if ($x !== false)
                    $excerpt = substr($excerpt, 0, $x) . '...';
            }
        }
        $s = $options['body'];
        $s = str_replace('{link}', $l, $s);
        $s = str_replace('{title}', $t, $s);
        $s = str_replace('{image}', $image, $s);
        $s = str_replace('{excerpt}', $excerpt, $s);

        $buffer .= $s;
    }
    $buffer .= $options['footer'];

    wp_reset_postdata();

    if ($echo)
        echo $buffer;
    else
        return $buffer;
}

$oposts_options = get_option('oposts');
if ($oposts_options['inject'] == 1) {
    if (!$oposts_options['priority'])
        $oposts_options['priority'] = 10;
    add_action('the_content', 'oposts_the_content', $oposts_options['priority']);
}

function oposts_the_content($content) {
    if (!is_single())
        return $content;
    return $content . oposts_show(false);
}

add_action('admin_head', 'oposts_admin_head');

function oposts_admin_head() {
    if (isset($_GET['page']) && strpos($_GET['page'], 'other-posts/') === 0) {
        echo '<link type="text/css" rel="stylesheet" href="' . plugins_url('admin.css', __FILE__) . '">';
    }
}

function oposts_activate() {
    global $wpdb;

    @$wpdb->query('ALTER TABLE ' . $wpdb->prefix . 'posts ADD FULLTEXT oposts_index (post_content, post_title)');

    @include(dirname(__FILE__) . '/languages/en_US_options.php');
    if (WPLANG != '')
        @include(dirname(__FILE__) . '/languages/' . WPLANG . '_options.php');

    $options = get_option('oposts');
    if (is_array($options)) {
        $options = array_merge($default_options, array_filter(get_option('oposts')));
        update_option('oposts', $options);
    } else {
        update_option('oposts', $default_options);
    }
}

function oposts_deactivate() {

    global $wpdb;

    $wpdb->query('DROP INDEX oposts_index ON ' . $wpdb->prefix . 'posts');
}

add_action('admin_menu', 'oposts_admin_menu');

function oposts_admin_menu() {
    add_options_page('Other Posts', 'Other Posts', 'manage_options', 'other-posts/options.php');
}

register_activation_hook(__FILE__, 'oposts_activate');
register_deactivation_hook(__FILE__, 'oposts_deactivate');
