# Vendored assets from the removed FOS bundles

`vendor-fos-templates.php` copied the two abandoned bundles' templates into
`next/templates/_fosuser` and `_foscomment`. One asset had to come with them:
`_foscomment/js/comments.js`, which `_foscomment/Thread/async.html.twig`
loads. It is taken verbatim from FOSCommentBundle at the revision
composer.lock pinned (`fa2a676`), the same revision `Base{Comment,Thread}`
were reproduced from.

`port-assetic.php` copies this tree into `next/public/bundles/`.
