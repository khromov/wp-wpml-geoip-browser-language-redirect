GeoIP Redirector for WPML
=======================================


This is a WordPress plugin that changes the browser language redirect in WPML from simple browser language detection to 
using a GeoIP database (MaxMind) to pinpoint user location more exactly.

## Usage

* Install this as a plugin
* Activate the plugin (WPML GeoIP Browser Language Redirect)
* Open the file WPML_GeoIP_IPResolver.class.php
* At the top, change your language mappings by modifying the $this->language_mappings array
* Change the default language mapping (when nothing else matches) by modyfing the $this->default_language variable
* Make sure WPML is enabled and that you have enabled the "Browser language redirect" in the WordPress admin, under WPML > Languages > Browser language redirect > Redirect visitors based on browser language only if translations exist (Enable this option)
