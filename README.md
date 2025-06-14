# Stormglass Astronomy Monthly Plugin

Displays sunrise, sunset, moonrise, moonset, and moon phase (with graphics) for a specified latitude and longitude using the Stormglass.io API. Features 30-day caching of API data, admin settings page for API key and coordinates, and a configurable widget.

## Description

This plugin fetches astronomical data (sunrise, sunset, moonrise, moonset, moon phase) from the Stormglass.io API for a given set of coordinates. It caches the data for 30 days to minimize API calls. You can display the data in your posts or pages using a shortcode or in your site's sidebars using the provided widget.

The plugin requires a Stormglass API key. You can set your API key, latitude, and longitude on the plugin's settings page under "Settings" > "Stormglass Astronomy".

## Shortcode Usage

The plugin provides the `[stormglass_astronomy]` shortcode to display astronomical data.

### Attributes:

*   `start`: (Optional) Specify a start date in YYYY-MM-DD format. If provided, `period` is ignored.
    Example: `[stormglass_astronomy start="2024-07-01"]`
*   `end`: (Optional) Specify an end date in YYYY-MM-DD format. If provided, `period` is ignored.
    Example: `[stormglass_astronomy start="2024-07-01" end="2024-07-15"]`
*   `fields`: (Optional) A comma-separated list of data fields to display. Available fields: `date`, `sunrise`, `sunset`, `moonrise`, `moonset`, `moonphase`. Defaults to `all`.
    Example: `[stormglass_astronomy fields="date,sunrise,sunset,moonphase"]`
*   `period`: (Optional) Set to `30days` to display a rolling 30-day forecast starting from the current date. This is ignored if `start` or `end` attributes are specified.
    Example: `[stormglass_astronomy period="30days"]`

If no `start`, `end`, or `period` attributes are provided, the display defaults to the current calendar month.

## Widget Usage

The plugin also provides a "Stormglass Astronomy" widget. You can configure it under "Appearance" > "Widgets".

### Widget Options:

*   **Title**: Custom title for the widget.
*   **Start Date (YYYY-MM-DD)**: Optional specific start date.
*   **End Date (YYYY-MM-DD)**: Optional specific end date.
*   **Display Period**: Choose between "Current Month" or "Next 30 Days". This is used if Start Date and End Date are left blank.
*   **Fields**: Comma-separated list of fields to display (e.g., `date,sunrise,sunset,moonphase`). Defaults to `all`.

If Start Date or End Date are specified in the widget, they will override the "Display Period" selection.

## Installation

1.  Upload the `stormglass-astronomy-monthly` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to "Settings" > "Stormglass Astronomy" to configure your API key, latitude, and longitude.
4.  Use the shortcode `[stormglass_astronomy]` in your posts/pages or add the "Stormglass Astronomy" widget to your sidebars.

## Caching

The plugin caches API responses for 30 days to reduce API usage and improve performance. A "Clear Cache" button is available on the plugin settings page.
