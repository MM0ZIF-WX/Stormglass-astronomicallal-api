# Stormglass Astronomy Monthly WordPress Plugin

Displays sunrise, sunset, moonrise, moonset, and moon phase information (including icons) for a specific geographical coordinate using the Stormglass.io API. Features 30-day caching of API data, admin settings for easy configuration, and a WordPress widget for flexible display.

## Features

*   Fetches and displays daily:
    *   Sunrise time
    *   Sunset time
    *   Moonrise time
    *   Moonset time
    *   Moon phase (with graphical icon and text)
*   Data sourced from the [Stormglass.io API](https://stormglass.io).
*   Configurable latitude and longitude via WordPress admin settings.
*   Securely stores your Stormglass API key.
*   Caches API responses for 30 days to improve performance and reduce API calls.
*   Provides a shortcode `[stormglass_astronomy]` for embedding data into posts and pages.
*   Provides a widget for easy display in sidebars or other widgetized areas.
*   Allows customization of displayed data fields via shortcode/widget parameters.
*   Currently displays 7 days of astronomical data starting from today's date.

## Installation

1.  Download the plugin ZIP file (or clone the repository).
2.  Upload the plugin files to the `/wp-content/plugins/` directory, or install the ZIP file through the WordPress plugins screen directly.
3.  Activate the plugin through the 'Plugins' screen in WordPress.

## Configuration

After activating the plugin, you need to configure it:

1.  Navigate to **Settings > Stormglass Astronomy** in your WordPress admin panel.
2.  Enter your **Stormglass API Key**. You can obtain an API key by registering at [Stormglass.io](https://stormglass.io/documentation).
3.  Enter the **Latitude** and **Longitude** for the location you want to display astronomical data for.
4.  Click **Save Changes**.

### Clearing the Cache

The plugin caches API data for 30 days. If you need to clear the cache manually (e.g., after changing coordinates or if you suspect data issues), you can do so from the **Settings > Stormglass Astronomy** page by clicking the "Clear Cache" button.

## Usage

### Shortcode

You can display the astronomy data in your posts or pages using the `[stormglass_astronomy]` shortcode.

**Basic Usage (displays 7 days from today for configured location):**

`[stormglass_astronomy]`

**Parameters:**

The shortcode accepts the following optional parameters to customize the date range and fields displayed. *Note: While the plugin fetches data based on `start` and `end` for caching, the display is currently fixed to 7 days from today.*

*   `start` (string, YYYY-MM-DD): Defines the start date for the data fetch period. Defaults to the first day of the current month (`YYYY-MM-01`).
*   `end` (string, YYYY-MM-DD): Defines the end date for the data fetch period. Defaults to the last day of the current month (`YYYY-MM-TT`).
*   `fields` (string, comma-separated): Specifies which data fields to display. Defaults to `all`.
    *   Available fields: `date`, `sunrise`, `sunset`, `moonrise`, `moonset`, `moonphase`.
    *   Example: `[stormglass_astronomy fields="date,sunrise,sunset,moonphase"]`

### Widget

1.  Navigate to **Appearance > Widgets** in your WordPress admin panel.
2.  Find the "Stormglass Astronomy" widget.
3.  Drag and drop the widget into your desired sidebar or widget area.
4.  Configure the widget options:
    *   **Title:** (Optional) Title for the widget.
    *   **Start Date (YYYY-MM-DD):** (Optional) Start date for data fetching (see shortcode `start` parameter notes).
    *   **End Date (YYYY-MM-DD):** (Optional) End date for data fetching (see shortcode `end` parameter notes).
    *   **Fields:** (Optional) Comma-separated list of fields to display (see shortcode `fields` parameter).

## Troubleshooting

*   **No data displayed / API error message:**
    *   Ensure your Stormglass API key is correctly entered in the settings.
    *   Verify that the Latitude and Longitude are valid and correctly entered.
    *   Check if your Stormglass API key is active and has not exceeded its quota.
    *   Try clearing the cache from the plugin's settings page.
    *   Enable WordPress debugging (`WP_DEBUG` and `WP_DEBUG_LOG`) to check for PHP errors in `wp-content/debug.log`. The plugin includes `error_log` statements that might provide more details on API interactions.
*   **Incorrect times:**
    *   Times are based on UTC by default from the API and then formatted. Ensure your WordPress timezone settings are correct if you notice discrepancies related to your local time. The plugin currently displays times as provided by the API (typically UTC) and formats them as H:i.
*   **Only 11 days of data being fetched (or less than a full month):**
    *   The Stormglass.io API might have limitations on the number of days of data returned per request, especially on free or lower-tier plans. The plugin requests a full month, but the API response dictates the actual data obtained. The newly added logging (see server error logs) will show the raw API response.

## Developer Notes

*   Moon phase icons are Unicode characters. Ensure your website's character encoding is set to UTF-8 for proper display.
*   The plugin uses WordPress transients for caching.
*   Custom CSS can be applied using the class `.stormglass-astronomy-table` for the table and `.moon-phase` for the moon phase cell.

---

For support or to report issues, please visit the [plugin's GitHub repository](https://github.com/mm0zif-wx) (replace with actual link if different).
