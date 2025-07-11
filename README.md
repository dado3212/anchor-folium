# Folium

Folium is a theme for [Anchor](https://anchorcms.com) ([Github](https://github.com/anchorcms/anchor-cms)), a lightweight blog system that I use [on my blog](http://blog.alexbeals.com).

This is what it looks like:

<img width="1624" alt="Screenshot 2025-05-23 at 11 35 24 PM" src="https://github.com/user-attachments/assets/93f17c17-b325-4f46-9218-2ae7467814b5" />

## Fonts
- [X] Vollkolm (too thick)

## Additions

There are a number of additions I've made to the base code. This is probably not exhaustive, but it's a lot of them.

* https://blog.alexbeals.com/posts/anchor-modifications
* https://blog.alexbeals.com/posts/adding-captcha-to-anchor
* https://blog.alexbeals.com/posts/adding-lightbox-support-to-anchor
* https://blog.alexbeals.com/posts/minor-anchor-tweak-scrolling

There are also some that aren't documented.

Automatically making links open in a new tab  
`/anchor/libraries/markdown.php`
```diff
function _doAnchors_inline_callback($matches) {
   ...
-   $result = "<a href=\"$url\"";
+   $result = "<a target=\"_blank\" href=\"$url\"";
   ...
```

Copied over time constants from the official repo
`/anchor/language/en_GB/posts.php`
```diff
+  'time'         => 'Published on (GMT)',
+  'time_explain' => 'Pattern: YYYY-MM-DD HH:MM:SS',
+  'time_invalid' => 'Invalid time pattern',
```

and in the UI, to be able to adjust publication date.
`/anchor/views/posts/edit.php`
```diff
    <em><?php echo __('posts.slug_explain'); ?></em>
  </p>
+  <p>
+    <label><?php echo __('posts.time'); ?>:</label>
+    <?php echo Form::text('created', Input::previous('created', $article->created)); ?>
+    <em><?php echo __('posts.time_explain'); ?></em>
+  </p>
```

## Inspiration
* https://m65digest.substack.com/p/disassembling-crossroads-part-1
* https://ludic.mataroa.blog/blog/you-must-read-at-least-one-book-to-ride/
* https://www.nullpt.rs/breaking-the-4chan-captcha#getting-the-data
* https://raw.sh/posts/easy_reward_model_inference#user-content-fnref-1
* https://www.joshtumath.uk/posts/2024-11-08-how-a-bbc-navigation-bar-component-broke-depending-on-which-external-monitor-it-was-on/
* https://ntietz.com/blog/evolving-ergo-setup/#engineers_ref
* https://fleetwood.dev/posts/you-could-have-designed-SOTA-positional-encoding
* https://blog.adamcameron.me/2014/07/code-for-annotation-highlighter.html
* https://overreacted.io/what-is-javascript-made-of/
* https://worksinprogress.co/issue/the-world-of-tomorrow/
* https://blog.langworth.com/imposter-attack#more-target-work
* https://blog.stevenchun.me/2024/06/Capacity-Conduction-Convection/
* https://tracydurnell.com/2024/12/17/in-praise-of-the-hundred-page-idea/

* https://cefboud.github.io/posts/compression/ -> use this for top right jump around on Mobile
* Include post tags from https://entropicthoughts.com/the-most-mario-colour-revisited
* https://samcurry.net/hacking-subaru for dark styling, code

TODO
- Display category of post (or tags?)
- Handle dark mode

Reduced the Font Awesome from 160kb to 680 bytes with `pyftsubset fa-solid-900.woff2 --unicodes="U+f328,U+f121" --flavor="woff2" --output-file="fa-solid-900-subset.woff2"`, the only two characters that prism currently uses.
