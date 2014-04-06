jQuery(document).ready(function()
{
    var GEOIP_DEBUG = false;

    if(GEOIP_DEBUG)
    {
        //IE 8/9 fix
        if(!window.console)
        {
            var console = {
                log : function(){},
                warn : function(){},
                error : function(){},
                time : function(){},
                timeEnd : function(){}
            }
        }
    }

    if (jQuery.cookie != undefined)
    {
        if(GEOIP_DEBUG)
            console.log("Initializing geoip script");

        // Check if cookie are enabled
        jQuery.cookie('wpml_browser_redirect_test', '1');
        var cookie_enabled = jQuery.cookie('wpml_browser_redirect_test') == 1;
        jQuery.removeCookie('wpml_browser_redirect_test');

        //Only attempt redirection of cookies can be placed with jQuery.cookie
        if (cookie_enabled)
        {
            if(GEOIP_DEBUG)
                console.log("cookies enabled");

            var cookie_params = wpml_browser_redirect_params.cookie;
            var pageLanguage = wpml_browser_redirect_params.pageLanguage;
            var cookie_name = cookie_params.name;

            //Check if we already did a redirect, and if we didn't...
            if (!jQuery.cookie(cookie_name))
            {
                if(GEOIP_DEBUG)
                    console.log("We have not redirected yet.");

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
                    if(GEOIP_DEBUG)
                        console.log("Page language is not correct for the user");

                    var redirectUrl;
                    // First try to find the redirect url from parameters passed to javascript
                    var languageUrls = wpml_browser_redirect_params.languageUrls;

                    if (languageUrls[browserLanguage] != undefined)
                    {
                        if(GEOIP_DEBUG)
                            console.log("Found redirection in conditional 1");

                        redirectUrl = languageUrls[browserLanguage];
                    }
                    else if (languageUrls[browserLanguage.substr(0, 2)] != undefined)
                    {
                        if(GEOIP_DEBUG)
                            console.log("Found redirection in conditional 2");

                        redirectUrl = languageUrls[browserLanguage];
                    }
                    else
                    {
                        if(GEOIP_DEBUG)
                            console.log("The user should have been redirected, but we could not find the localized version of the page.");
                    }

                    //Finally do the redirect, if this pages language exists
                    if (redirectUrl != undefined)
                    {
                        if(GEOIP_DEBUG)
                            console.log("Redirecting user!");

                        window.location = redirectUrl;
                    }
                }
                else
                {
                    if(GEOIP_DEBUG)
                        console.log("User not redirected because he is already on the right language.");
                }
            }
            else
            {
                if(GEOIP_DEBUG)
                    console.log("User has already been redirected.");
            }
        }
    }
});