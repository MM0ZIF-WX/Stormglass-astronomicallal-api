# WeatherAPI Astronomy

Displays monthly astronomical data (sunrise, sunset, moonrise, moonset, moon phase, and illumination) for a specified location using the WeatherAPI.com service.

## Description

This plugin fetches daily astronomical data for an entire month from WeatherAPI.com and displays it in a table format. It includes:
- Sunrise and sunset times.
- Moonrise and moonset times.
- Moon phase (text description with corresponding icon).
- Moon illumination (percentage).
- Data is cached for 12 hours to minimize API calls.
- Provides an admin settings page to configure API key and location.
- Offers a shortcode and a widget for displaying the data.

## Installation

1.  Upload the `weatherapi-astronomy` folder to the `/wp-content/plugins/` directory on your WordPress site.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to "Settings" -> "WeatherAPI Astronomy Settings" in your WordPress admin area to configure the plugin.

## Configuration

After activation, you need to configure the following settings:

1.  **API Key:**
    *   Navigate to "Settings" -> "WeatherAPI Astronomy Settings".
    *   Enter your API key from [WeatherAPI.com](https://www.weatherapi.com/). You can sign up for a free API key which includes access to the Astronomy API.
2.  **Latitude & Longitude:**
    *   Enter the latitude and longitude for the location you want to display astronomy data for.

Save the settings. The plugin will not work until these are correctly configured.

## Usage

### Shortcode

You can display the monthly astronomy data table in any post or page using the following shortcode:

`[weatherapi_astronomy]`

This will display data for the current month by default.

### Widget

The plugin also provides a "WeatherAPI Astronomy" widget.

1.  Navigate to "Appearance" -> "Widgets" in your WordPress admin area.
2.  Drag the "WeatherAPI Astronomy" widget to your desired sidebar or widget area.
3.  Configure the widget title (optional). The widget will use the globally configured API key and location.

## Caching

The plugin caches the fetched monthly astronomy data for 12 hours to reduce the number of API calls made to WeatherAPI.com. You can manually clear this cache by going to "Settings" -> "WeatherAPI Astronomy Settings" and clicking the "Clear Astronomy Cache" button.

## CSS Styling

Basic styling for the table is provided in `css/style.css`. You can override these styles in your theme's stylesheet if needed. The main wrapper div has the class `weatherapi-astronomy-wrapper` and the table itself has the class `weatherapi-astronomy-table`.
