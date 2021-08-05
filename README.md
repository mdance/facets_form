# Facets Form

Exposes facets from a facets source as Drupal Form API elements.

## Why?

Even displayed as a dropdown select or checkboxes, the facet widgets are
rendered as link lists. Then a JavaScript snippet is responsible to convert them
into form elements, such as `<select>` or `<input type="checkbox">`.

This has some drawbacks:
* Even the widgets are displayed as form elements, they are not piped through
  Drupal Form API. As a consequence, third-party code cannot perform form
  alterations, and they are not subject to standard Form API elements theming.
* The current Facets implementation is applying the filter as soon as the
  element is selected or checked. This is a UX issue, mostly on mobile devices.
  Also, it doesn't allow scenarios where the user wants to do multiple filter
  selection before the filters are submitted.

## Dependency

This module depends on [Facets](https://www.drupal.org/project/facets) and
requires [this patch](https://www.drupal.org/project/facets/issues/3223956). If
you use Composer, the patch will be automatically downloaded for you, otherwise
you'll need to apply the patch manually. Please, support the patch adoption in
https://www.drupal.org/project/facets/issues/3223956.

## Usage

* _Facets Form_ provides a block containing a form for each facets source.
* By default, each block is exposing all _eligible facets_. But the site builder
  is able to limit the list of facets to be exposed from the block
  configuration.
* The "submit" and "reset" buttons labels can be also be configured on block
  level.
* _Eligible facets_ are all facets using widgets plugins that are extending the
  `\Drupal\facets_form\FacetsFormWidgetInterface`. _Facets Form_ module ships
  with two widgets, aiming to provide a Form API alternative to _Facets_ module
  native widgets:
  * Dropdown (inside form) `facets_form_dropdown`
  * Checkboxes (inside form) `facets_form_checkbox`
* The `facets_form_date_range` sub-module provides a third, bonus, date range
  widget.

## Extending

Third-party modules can provide additional widgets to be usable inside forms, by
implementing the `\Drupal\facets_form\FacetsFormWidgetInterface` interface.

## Authors

* Claudiu Cristea, https://www.drupal.org/u/claudiucristea
* Andras Szilagyi, https://www.drupal.org/u/andras_szilagyi
