# Folium

Folium is a theme for [Anchor](https://anchorcms.com), a lightweight blog system that I use [on my blog](http://blog.alexbeals.com).

This is what it looks like:

![Folium screenshot](/Folium.png "Folium screenshot")

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
