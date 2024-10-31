<?php
$options = get_option('oposts');

if (isset($_POST['defaults']))
{
    if (!check_admin_referer()) die('Securety violated');
    @include(dirname(__FILE__) . '/languages/en_US_options.php');
    @include(dirname(__FILE__) . '/languages/' . WPLANG . '_options.php');

    update_option('oposts', $default_options);
    $options = $default_options;
}

if (isset($_POST['save']))
{
    if (!check_admin_referer('save')) die('Securety violated');
    $options = stripslashes_deep($_POST['options']);
    update_option('oposts', $options);
}

?>
<div class="wrap">
    
    <div id="satollo-header">
        <a href="http://www.satollo.net/plugins/other-posts" target="_blank">Get Help</a>
        <a href="http://www.satollo.net/forums" target="_blank">Forum</a>

        <form style="display: inline; margin: 0;" action="http://www.satollo.net/wp-content/plugins/newsletter/do/subscribe.php" method="post" target="_blank">
            Subscribe to satollo.net <input type="email" name="ne" required placeholder="Your email">
            <input type="hidden" name="nr" value="other-posts">
            <input type="submit" value="Go">
        </form>
        <!--
        <a href="https://www.facebook.com/satollo.net" target="_blank"><img style="vertical-align: bottom" src="http://www.satollo.net/images/facebook.png"></a>
        -->
        <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5PHGDGNHAYLJ8" target="_blank"><img style="vertical-align: bottom" src="http://www.satollo.net/images/donate.png"></a>
        <a href="http://www.satollo.net/donations" target="_blank">Even <b>2$</b> helps: read more</a>
    </div>    
    <h2>Other Posts</h2>

    <p>
        Other Posts shows a list of related posts under each published post of your blog. More information available on
        <a href="http://www.satollo.net/plugins/other-posts" target="_blank">Other Posts</a> official page and on
        <a href="http://www.satollo.net/forums/forum/other-posts" target="_blank">Other Posts forum</a>.
        This plugin uses the "full text search" feature of MySQL which compute a score and returns the most relevant
        related posts.
    </p>

    <form method="post">
        <?php wp_nonce_field('save') ?>

        <table class="form-table">
            <tr valign="top">
                <th><label>Automatically show related posts?</label></th>
                <td>
                    <select name="options[inject]">
                        <option value="1" <?php echo $options['inject']=='1'?'selected':''; ?>>Yes, add them after the post content</option>
                        <option value="0">No, I'll modify the theme manually</option>
                    </select>
                    <div>
                        If you want to manually add related posts changing your theme, use the snippet:<br /><br />
                        &lt;?php if (function_exists('oposts_show')) oposts_show(); ?&gt;<br /><br />
                        that should be usually added to the single.php file.
                    </div>
                </td>
            </tr>
            <tr valign="top">
                <th><label>Priority</label></th>
                <td>
                    <input name="options[priority]" type="text" size="10" value="<?php echo htmlspecialchars($options['priority'])?>"/>
                    <div>
                        Since other plugin can add things after the post content, changeing the priority you should be able to
                        move the related posts before or after other plugins "injections".
                        <br />
                        This value is 10 by default: greater the number, later the related posts will be added after the
                        post content.
                    </div>
                </td>
            </tr>

            <tr valign="top">
                <th><label>Packaged templates</label></th>
                <td>
                    <select name="options[template]">
                        <option value="1" <?php echo $options['template']=='1'?'selected':''; ?>>Template 1</option>
                        <option value="2" <?php echo $options['template']=='2'?'selected':''; ?>>Template 2</option>
                        <option value="3" <?php echo $options['template']=='3'?'selected':''; ?>>Plain list</option>
                        <option value="4" <?php echo $options['template']=='4'?'selected':''; ?>>Horizontal responsive</option>
                        <option value="0" <?php echo $options['template']=='0'?'selected':''; ?>>Custom (see below)</option>
                    </select>
                    <div>
                        <img src="<?php echo plugins_url('', __FILE__); ?>/template-1.png"/>
                        <img src="<?php echo plugins_url('', __FILE__); ?>/template-2.png"/>
                        <img src="<?php echo plugins_url('', __FILE__); ?>/template-3.png"/>
                        <img src="<?php echo plugins_url('', __FILE__); ?>/template-4.png"/>

                        <br />
                        The header and footer parts (see below) will be added even when you choose a packaged theme, so you can
                        add a title like "Related posts" or "You may be interested in".
                    </div>
                </td>
            </tr>
            <tr valign="top">
                <th><label>Custom template</label></th>
                <td>
                    header:<br />
                    <textarea name="options[header]" wrap="off" rows="5" cols="75"><?php echo htmlspecialchars($options['header'])?></textarea>
                    <br /><br />

                    repeated block for each related post:<br />
                    <textarea name="options[body]" wrap="off" rows="5" cols="75"><?php echo htmlspecialchars($options['body'])?></textarea>
                    <br /><br />

                    footer:<br />
                    <textarea name="options[footer]" wrap="off" rows="5" cols="75"><?php echo htmlspecialchars($options['footer'])?></textarea>

                    <div>
                        Tags you can user: {title} for the post title, {link} for the post permalink, {excerpt} for the content excerpt,
                        {image} for the first post image url.<br />
                        If you want to generate an HTML table, the header must contains the &lt;table&gt; opening tag and the footer
                        must contains the &lt;/table&gt; closing tag. You should know the HTML to do that in a correct way.
                    </div>

                </td>
            </tr>
            <tr valign="top">
                <th><label>Search images in HTML</label></th>
                <td>
                    <input name="options[useimg]" type="checkbox" <?php echo isset($options['useimg'])?'checked':''; ?>/>
                    <div>
                        If enabled when a post as no images in his gallery, images are searched directly inside its content.
                    </div>
                </td>
            </tr>            
            <tr valign="top">
                <th>Alternative thumbnail URL</th>
                <td>
                    <input name="options[thumb]" type="text" size="50" value="<?php echo htmlspecialchars($options['thumb'])?>"/>
                    <div class="hints">
                        Used when no thumbnail can be found on a related post.
                    </div>
                </td>
            </tr>

            <tr valign="top">
                <th><label>Max posts to show</label></th>
                <td>
                    <input name="options[max]" type="text" size="10" value="<?php echo htmlspecialchars($options['max'])?>"/>
                </td>
            </tr>
            <tr valign="top">
                <th><label>Excerpt length</label></th>
                <td>
                    <input name="options[excerpt]" type="text" size="10" value="<?php echo htmlspecialchars($options['excerpt'])?>"/>
                    <div>
                        Set to zero if you do not want the excerpt (if you use a custom template, see above, you should not use
                        the {excerpt} tag).
                    </div>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="save" value="Save"/>
            <input type="submit" name="defaults" value="Revert to Defaults" onclick="return confirm('Are you sure?')"/>
        </p>
    </form>
</div>

