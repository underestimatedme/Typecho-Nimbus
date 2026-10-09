<?php
use Nimbus\Widgets;

test('order default when empty', fn() => assert_eq(['author', 'notice', 'hot', 'recent-comments', 'tags', 'categories'], Widgets::order('')));
test('order parses, trims, ignores comments & unknown & dupes', fn() => assert_eq(['hot', 'author'], Widgets::order(" hot \n# notice\nnope\nauthor\nhot")));
test('order accepts comma separated', fn() => assert_eq(['tags', 'calendar'], Widgets::order('tags, calendar')));
