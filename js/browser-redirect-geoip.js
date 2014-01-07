jQuery(document).ready(function ()
{
    if (jQuery.cookie != undefined)
    {
        // Check if cookie are enabled
        jQuery.cookie('wpml_browser_redirect_test', '1');
        var cookie_enabled = jQuery.cookie('wpml_browser_redirect_test') == 1;
        jQuery.removeCookie('wpml_browser_redirect_test');

        //Only attempt redirection of cookies can be placed with jQuery.cookie
        if (cookie_enabled)
        {
            var cookie_params = wpml_browser_redirect_params.cookie;
            var pageLanguage = wpml_browser_redirect_params.pageLanguage;
            var cookie_name = cookie_params.name;

            //Check if we already did a redirect, and if we didn't...
            if (!jQuery.cookie(cookie_name))
            {
                //Get the country code to use by IP
                jQuery.ajax({
                    type: 'GET',
                    data: {'wpml_geoip' : 1},
                    async: false,
                    success: function (ret)
                    {
                        browserLanguage = ret.country_code
                    }
                });

                // Build cookie options
                var cookie_options = {
                    expires: cookie_params.expiration / 24,
                    path: cookie_params.path ? cookie_params.path : '/',
                    domain: cookie_params.domain ? cookie_params.domain : ''
                };

                // Set the cookie so that the check is made only on the first visit
                jQuery.cookie(cookie_name, browserLanguage, cookie_options);

                // Compare page language and browser language
                if (pageLanguage != browserLanguage)
                {
                    //console.log("gasp! page lang isn't browser lang!");

                    var redirectUrl;
                    // First try to find the redirect url from parameters passed to javascript
                    var languageUrls = wpml_browser_redirect_params.languageUrls;

                    if (languageUrls[browserLanguage] != undefined)
                    {
                        redirectUrl = languageUrls[browserLanguage];
                    }
                    else if (languageUrls[browserLanguage.substr(0, 2)] != undefined)
                    {
                        redirectUrl = languageUrls[browserLanguage];
                    }
                    //Finally do the redirect, if this pages language exists
                    if (redirectUrl != undefined)
                    {
                        window.location = redirectUrl;
                    }
                }
            }
        }
    }
});
