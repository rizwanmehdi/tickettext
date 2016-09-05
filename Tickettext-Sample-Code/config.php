<?php
// Main config file.
// Sets up data and layout info for all pages under this one.
//---------------------------------------------------------------//

    // Includes.
        require_once 'includes.php';
        require_once 'locale.php';           // Localization configurations.

        date_default_timezone_set('Europe/London');       // @see constants.php
        
       // if(DOMAIN == DOMAIN_PARTIAL) redirect('https://www.' . DOMAIN_PARTIAL);      
        
        /*$current_link = $_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI];
        
        if($current_link == 'https://demo.demo.com/'){
            header("Location: https://demo.demo.com/demo/");
            die;
        }*/
    // Shortcut vars.

        global $site;
        global $root_page;
        $site = $this->_site;
        $page = $site->page; // Reference to the 'active' page.
        $root_page = $page;
        $p_json = $this->page('json');
        $p_pages = $this->page('pages');

    // DB.
        $site->db(DB_DB)->user(DB_USER)->password(DB_PASS);

    // UTM Source
        if(UTM_SOURCE) setcookie('utm_source', UTM_SOURCE, time()+60*60*24*30, '/', DOMAIN, true, false);

    // Allowed domains
        $domains = array();
        if(ALLOWED_DOMAINS) $domains = explode(',', ALLOWED_DOMAINS);

    //  White labels sub-domains objects.
        $q = $site->query('whitelabel')->is('active', 'status');
        $a = array();
        $cmp = false;

        foreach($q->rows() as $r) $a[] = $r;

        if(is_array($a) && !empty($a))
        {

           foreach($a as $r)
           {
                $o = strtoupper(str_replace('-', '_', $r->subdomain));  // Replaces dashed to underline and uppercase.
                $pos = stripos(DOMAIN, '.', 0);

                if($pos !== false && $chunks = explode('.', DOMAIN))
                {
                    if(!empty($chunks) && $chunks[0] == $r->subdomain) $cmp = $chunks[0];

                } else $cmp = false;


                // Check if in list and define

                if(!in_array($r->subdomain . '.' . DOMAIN_PARTIAL, $domains) && $cmp)
                {
                    $domains[] = $r->subdomain . '.' . DOMAIN_PARTIAL;

                    $o = strtoupper(str_replace('-', '_', $r->subdomain));  // Replaces dashed to underline and uppercase.
                    $pos = stripos(DOMAIN, '.');


                    if($pos !== false && !defined('IS_' . $o) && ($r->subdomain == 'bsc' || $r->subdomain == 'boat-show-comedy') && $r->subdomain == $cmp)
                    {
                        define('IS_BOAT_SHOW_COMEDY', true);
                        define('IS_BSC', true);
                    }
                    // Dynamic white label constants. For Example: IS_PROMOTER_SLUG
                    elseif($pos !== false && !defined('IS_' . $o) && !($r->subdomain == 'bsc' || $r->subdomain == 'boat-show-comedy') && $r->subdomain == $cmp)
                    {
                        define('IS_' . $o, true);
                    }



                    // Active white label
                    if($pos !== false && $r->subdomain == $cmp)
                    {
                        $b = array(
                            'id'       => $r->id,
                            'promoter' => $r->promoter,
                            'name'     => $r->subdomain,
                            'folder'   => $r->folder,
                            'logo'     => $r->logo,
                            'status'   => $r->status,
                            'title'    => $r->title,
                            'url'      => $r->url,
                            'ballot_allowed' => $r->ballot_allowed
                        );

                        // Shortcuts
                        defined('IS_WHITE_LABEL') || define('IS_WHITE_LABEL', true);
                        defined('WHITE_LABEL_SLUG') || define('WHITE_LABEL_SLUG', $r->subdomain);
                        defined('WHITE_LABEL_TITLE') || define('WHITE_LABEL_TITLE', $r->title);
                        defined('WHITE_LABEL_PROMOTER_ID') || define('WHITE_LABEL_PROMOTER_ID', $r->promoter);
                        defined('WHITE_LABEL_LOGO') || define('WHITE_LABEL_LOGO', $r->logo);
                        defined('WHITE_LABEL_DETAILS') || define('WHITE_LABEL_DETAILS', serialize($b));
                        defined('WHITE_LABEL_URL') || define('WHITE_LABEL_URL', 'https://' . WHITE_LABEL_SLUG . '.' . DOMAIN_PARTIAL);
                        unset($b);
                    }


                    unset($b);
                    unset($chunks);
                }


           }

           foreach($a as $r)
           {

                $o = strtoupper(str_replace('-', '_', $r->subdomain));  // Replaces dashed to underline and uppercase.
                $pos = stripos(DOMAIN, '.', 0);

                if($pos !== false && $chunks = explode('.', DOMAIN))
                {
                    if(!empty($chunks) && $chunks[0] == $r->subdomain) $cmp = $chunks[0];

                } else $cmp = false;


                if($pos !== false && !defined('IS_' . $o) && ($r->subdomain == 'bsc' || $r->subdomain == 'boat-show-comedy'))
                {
                    define('IS_BOAT_SHOW_COMEDY', false);
                    define('IS_' . $o, false);
                }
                else
                {
                    define('IS_' . $o, false);
                }
           }
        }




    defined('AVAILABLE_DOMAINS') || define('AVAILABLE_DOMAINS', implode(',', $domains));

    // Redirect to live if not in approved list

        if(!in_array(DOMAIN, $domains)) redirect(LIVE_URL);
        unset($domains); // clear array

    // check if white label
        if(!defined('IS_WHITE_LABEL')) define('IS_WHITE_LABEL', false);


    // Objects.
        require_once 'includes.php';         // Includes scripts
        include './functions/event.php';                    // Event functions
        include './functions/sendgrid.php';                 // Replaces email module with SendGrid
        include './functions/ticket.php';                   // Ticket functions
        include './objects.php';                            // DB objects and functions
        include './functions/route.php';                    // Handle routing functions
        include './functions/mailinglist.php';              // Mailing list functions
        include './functions/whitelabel.php';               // Whitelabel functions
        include './functions/performers.php';               // Performers functions
        include './functions/ballot.php';                   // Ballot tickets functions
        //include './functions/evvnt/api.php';                // Evvnt API.

    // Users.

        $customer = $site->customer; // Logged in customer object.
        $promoter = $site->promoter; // Logged-in promoter object.
        $admin = $site->admin; // Logged in admin object.
        $venue_user = $site->venue_user; // Logged in venue object

    // Data.
        if(!IS_WHITE_LABEL)
        {
            $this->_site
            ->title('Ticket Text')
            ->data('from', EMAIL_FROM)
            ->data('to', EMAIL_TO);
        }
        else
        {
            $this->_site
            ->title(WHITE_LABEL_TITLE)
            ->data('from', EMAIL_FROM)
            ->data('to', EMAIL_TO);
        }

    // Main sections.



        // API v1.
        if(!IS_WHITE_LABEL)
        {
            $p_api1 = $this->page('api1');
            if ($p_api1->active || $p_api1->proud) return;

            // Json public api
            if($p_json->active || $p_json->proud) return;

            // Admin area
            $p_admin = $this->page('admin')->title('Admin');
            $p_admin->page('login')->title('Login');
            $p_admin->page('register')->title('Register');
            $p_admin->page('logout')->title('Logout');


            // Profile promoter check.
                if(session_status() != PHP_SESSION_ACTIVE) session_start();
                if(isset($_SESSION['profile']) && isset($_SESSION['profile']['name']))
                {

                    $profile_name = $_SESSION['profile']['name']? $_SESSION['profile']['name'] : 'undefined';
                    $appeneded_title = ' ( ' . $profile_name . ' )';

                } else $appeneded_title = '';

                if(session_status() == PHP_SESSION_ACTIVE) session_write_close();

            // Promoter page.
                $p_promoter = $this->page('promoter');
                $p_promoter->page('login')->title('Login');
                $p_promoter->page('register')->title('Register');
                $p_promoter->page('logout')->title('Logout');


                if ($promoter) $p_promoter->title('My account' . $appeneded_title);
                else $p_promoter->title('Promoters');

            // Venue User area.
                $p_venue = $this->page('venue');
                $p_venue->page('login')->title('Login');
                $p_venue->page('register')->title('Register');
                $p_venue->page('logout')->title('Logout');

                if($venue_user) $p_venue->title('My Account');
                else
                {
                    $p_venue->title('Venues');

                }

                $p_news = $this->page('news');

                if($p_news->active){
                    $this->redirect('http://blog.demo.com/');
                }

            // Sell tickets page
                $p_sell_tickets = $this->page('sell-tickets');
        }

        // Customer area.
            $p_customer = $this->page('customer');
            $p_customer->page('login')->title('Login');
            $p_customer->page('register')->title('Register');
            $p_customer->page('logout')->title('Logout');
            if ($customer) $p_customer->title('My account');

        // Help Page
            $p_help = $this->page('contact')->title('Contact Us');;

        // Checkout page.
            $p_checkout = $this->page('checkout')->title('Checkout');

        // PayPal IPN page.
            $p_paypal = $this->page('paypal');

        // Ticket page.
            $p_ticket = $this->page('ticket')->title('Ticket');

        // Contact.
            $p_contact = $this->page('contact')->class('layout-default')->title('Contact Us');

        // News
            if(!IS_WHITE_LABEL)  $this->page('news')->title('News');

        // Help Page
            if(!IS_WHITE_LABEL) $this->page('help')->title('Help');

        // Search Page
            $p_search = $this->page('search')->title('Search');

        // Customer register page
            $p_customer_register = $p_customer->page('register')->title('Register');


        if(($customer && $customer != 0) | $promoter | $admin | $venue_user)
        {
            defined('IS_LOGGED_IN') || define('IS_LOGGED_IN', true);
        } else defined('IS_LOGGED_IN') || define('IS_LOGGED_IN', false);

        // Layout.
        // Set some site-wide styles and scripts.
        $site->unstyle()
            ->icon('favicon.ico')
            ->style('https://code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css')
            ->style('assets/css/ie.css?' . CACHEBUST, 'lt IE 9')
            ->script('assets/js/consistent.min.js?' . CACHEBUST)
            ->script('https://use.typekit.net/don3fdh.js')
            ->script('try{Typekit.load();}catch(e){}')
            ->script('https://code.jquery.com/ui/1.11.1/jquery-ui.min.js')
            ->script('assets/js/jquery-ui.multidatespicker.js')
            ->script('assets/js/jquery.cookie.min.js')
            ->script('assets/js/jquery.ajaxQueue.min.js');

        
        $site->style('assets/css/master.css?' . CACHEBUST);
        if(IS_WHITE_LABEL && file_exists('assets/css/whitelabels/' . WHITE_LABEL_SLUG . '.css')){ die('here'); 
            $site->style('assets/css/whitelabels/' . WHITE_LABEL_SLUG . '.css');                    
        }else{
            $site->style('assets/css/whitelabels/demo.css');                    
        }

        // Some things that apply to all pages.
        $page->untitled();
        if(IS_ROOT_PAGE && !IS_WHITE_LABEL) $site->class('non-whitelabel');
        $page->tag('body')->class('main-container');

        if(IS_ROOT_PAGE) $page->class('index-page');
        
        /*for browse page*/
        if(IS_BROWSE_PAGE && !IS_WHITE_LABEL) $site->class('non-whitelabel');
        if(IS_BROWSE_PAGE) $page->class('home-page');

        
        if(IS_LOGGED_IN) $page->class('logged-in');



        if(!$admin)
        {
            $visitorType = 'customer';
            $visitorStatus = 'logged-out';
            $visiter_id = 0;
            $gtmGaAccount = 'UA-38956152-1';
            $gtm_variables = array();
            $pageType = '';

            $customer = $site->customer;
            $promoter = $site->promoter;
            $venue_user = $site->venue_user;

            if(!$customer && $promoter && !$admin &&!$venue_user) $visitorType = 'promoter';
            if($customer || $promoter || $venue_user)
            {
                $visitorStatus = 'logged-in';
            }

            if($customer){
                $visiter_id = $customer->id;
            }

            if($promoter){
                $visiter_id = $promoter->id;
            }

            if($venue_user){
                $visiter_id = $venue_user->id;
            }


            if($this->active)
            {
                $pageType = 'homepage';
                $pageName = 'homepage';
            }
            else
            {
                if($next = $this->next)
                {
                    $pageName = $next->_name;
                    $p = $this->page($next);

                    switch(strtolower($pageName))
                    {
                        case 'contact':
                            $pageType = 'contact';
                            break;
                        case 'checkout':
                            $pageType = 'checkout process';
                            break;
                        case 'login':
                            $pageType = 'account';
                            break;
                        case 'customer':
                            $pageType = 'account';
                            if($p->next){
                                $pageName = $pageName . '/' . $p->next->_name;
                            }
                            break;
                        case 'promoter':
                            $pageType = 'account';
                            if($p->next){
                                $pageName = $pageName . '/' . $p->next->_name;
                            }

                            break;
                        case 'venue':
                            $pageType = 'account';
                            if($p->next){
                                $pageName = $pageName . '/' . $p->next->_name;
                            }
                            break;
                        case 'ticket':
                            $pageType = 'ticket page';
                            break;
                    }

                    if ($promoter = $this->promoter($pageName, 'slug'))
                    {

                        if($p->next && $_SERVER['REQUEST_URI'] != '/the-producers/southampton/'){
                            $pageType = 'ticket page';
                            $event = $this->query('event')->is($promoter->id, 'promoter')->row();
                            $pageName = $pageName . '/' . $p->next->_name;

                            $gtm_variables['eventDate'] = date('d/m/Y', (int)$event->date_to);
                            $gtm_variables['eventGenre'] = $event->category_name;
                            $gtm_variables['eventLocation'] = $event->location;
                            $gtm_variables['eventName'] = str_replace("'", '', htmlspecialchars($event->name));
                            $gtm_variables['eventPromoter'] = str_replace("'", '', htmlspecialchars($event->promoter_name));
                            $gtm_variables['eventPublicationDate'] =  date('d/m/Y', (int)$event->date_from);
                            $gtm_variables['eventVenue'] = $event->venue_name;

                        }else{
                            $pageType = 'events list';
                        }
                    }
                    elseif ($category = $this->category($pageName, 'slug'))
                    {
                        $pageType = 'events list';
                    }
                    elseif($information = $this->information($pageName, 'slug'))
                    {
                        $pageType = 'company';
                    }
                    elseif ($location = $this->location($pageName, 'slug')) {
                        $pageType = 'event list';
                    }

                }
            }
            
            if($pageName == 'browse'){
                $this->active = true;
                $this->next = '';
                $pageName = 'homepage';
                $pageType = 'homepage';
            }
            // Virtual Page GT-LAYER
                $pageName = '/virtual/' . $pageName;

            // Header block.
                $gtm_variables['gaAccount'] = $gtmGaAccount;
                $gtm_variables['visitorType'] = $visitorType;
                $gtm_variables['visitorStatus'] = $visitorStatus;
                $gtm_variables['userID'] = $visiter_id;
                $gtm_variables['pageType'] = $pageType;
                $gtm_variables['pageName'] = $pageName;
                if(!IS_PLUGIN_PAGE)
                $gtm_layer = $page->block('gtm-layer')->file('gtm-layer.php')->data('gtm_variables', $gtm_variables);
        }

        // Logged in customer object.
            $customer = $site->customer;
            $promoter = $site->promoter;
            $venue_user = $site->venue_user;


        // Blocks   
            if(IS_HAT_TRICK_PRODUCTIONS){
                $hc_container = $page->block('hc-container')->tag('div');    // Header and nav wrapper
                $bw_header = $hc_container->block('header-wrapper')->tag('div');    // Header wrapper
                $b_header = $bw_header->block('header')->tag('header');     // Header            
            }elseif (IS_PLUGIN_PAGE) {
                $site->script('assets/js/plugin-fix.js');
                $site->style('assets/css/plugin-fix.css');
                $main_div = $page->block('main-div')->tag('div');                           
            }else {
                $bw_header = $page->block('header-wrapper')->tag('div');    // Header wrapper
                $b_header = $bw_header->block('header')->tag('header');     // Header            
            }
            $b_nav = null;  // Navigation
            
            
			// Not in every menu Whitelabel
            if ($admin->active && !IS_WHITE_LABEL )
            {
                $link = new stdClass();
                $link->title = 'Logout';
                $link->_name = strtolower('Logout');
                $link->_id = mt_rand(955,9999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'admin/logout/';
                $link->fake = true;
                $admin_logout_link = $link;

                $link = new stdClass();
                $link->title = 'My Account';
                $link->_name = strtolower('My Account');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'admin/';
                $admin_link = $link;


                $link = new stdClass();
                $link->title = 'Contact Us';
                $link->_name = strtolower('Contact Us');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'contact/';
                $contact_us_link = $link;

                $nav = array(
                    $admin_link,
                    $contact_us_link,
                    $admin_logout_link,
                );
            }
            elseif ($promoter->active && !IS_WHITE_LABEL)
            {
                $link = new stdClass();
                $link->title = 'Logout';
                $link->_name = strtolower('Logout');
                $link->_id = mt_rand(955,9999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'promoter/logout/';
                $promoter_logout_link = $link;

                $link = new stdClass();
                $link->title = 'Contact Us';
                $link->_name = strtolower('Contact Us');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'contact/';
                $contact_us_link = $link;

                $nav = array(
                    $p_promoter,
                    $contact_us_link,
                    $promoter_logout_link,
                );
            }
            elseif ($customer->active)
            {

                $link = new stdClass();
                $link->title = 'Logout';
                $link->_name = strtolower('Logout');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = false;
                $link->url = URL.'customer/logout/';
                $customer_logout_link = $link;

                $link = new stdClass();
                $link->title = 'My Account';
                $link->_name = strtolower('My Account');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'customer/';
                $customer_link = $link;

                $link = new stdClass();
                $link->title = 'Contact Us';
                $link->_name = strtolower('Contact Us');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'contact/';
                $contact_us_link = $link;

                $nav = array(

                    $customer_link,
                    $contact_us_link,
                    $customer_logout_link,
                );
            }
            elseif ($venue_user->active && !IS_WHITE_LABEL)
            {
                $link = new stdClass();
                $link->title = 'Logout';
                $link->_name = strtolower('Logout');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'venue/logout/';
                $venue_logout_link = $link;

                $link = new stdClass();
                $link->title = 'My Account';
                $link->_name = strtolower('My Account');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'venue/';
                $venue_link = $link;

                $link = new stdClass();
                $link->title = 'Contact Us';
                $link->_name = strtolower('Contact Us');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'contact/';
                $contact_us_link = $link;

                $nav = array(

                    $venue_link,
                    $contact_us_link,
                    $venue_logout_link,
                );
            }
            elseif(!IS_PLUGIN_PAGE && !IS_WHITE_LABEL && !IS_BROWSE_PAGE && !IS_ROOT_PAGE && !($venue_user->active) && ($customer->id != 0) && !($p_admin->proud == true || $p_promoter->proud == true || $p_customer->proud == true))
            {
                $link = new stdClass();
                $link->title = 'Login';
                $link->_name = strtolower('Login');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'venue/login/';

                $venue_login_link = $link;

                $link = new stdClass();
                $link->title = 'Register';
                $link->_name = strtolower('Register');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'venue/register/';

                $venue_register_link = $link;

                $link = new stdClass();
                $link->title = 'Contact Us';
                $link->_name = strtolower('Contact Us');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'contact/';
                $contact_us_link = $link;

                $nav = array(
                    $p_news,
                    'venue' => 'Manage your venues',
                    $contact_us_link,
                    $venue_login_link,
                    $venue_register_link,
                );
            }
            elseif(!IS_WHITE_LABEL && $p_promoter->active && !IS_LOGGED_IN)
            {
                $link = new stdClass();
                $link->title = 'Login';
                $link->_name = strtolower('Login');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'promoter/login/';

                $promoter_login_link = $link;

                $link = new stdClass();
                $link->title = 'Register';
                $link->_name = strtolower('Register');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'promoter/register/';

                $promoter_register_link = $link;

                $link = new stdClass();
                $link->title = 'Contact Us';
                $link->_name = strtolower('Contact Us');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'contact/';
                $contact_us_link = $link;

                $nav = array(
                    $p_news,
                    'promoter' => 'List your event',
                    $contact_us_link,
                    $promoter_login_link,
                    $promoter_register_link
                );
            }
            elseif(!IS_WHITE_LABEL && $p_promoter->active && !IS_LOGGED_IN)
            {
                $link = new stdClass();
                $link->title = 'Login';
                $link->_name = strtolower('Login');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'promoter/login/';

                $promoter_login_link = $link;

                $link = new stdClass();
                $link->title = 'Register';
                $link->_name = strtolower('Register');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'promoter/register/';

                $promoter_register_link = $link;

                $link = new stdClass();
                $link->title = 'Contact Us';
                $link->_name = strtolower('Contact Us');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'contact/';
                $contact_us_link = $link;

                $nav = array(
                    $p_news,
                    'promoter' => 'List your event',
                    $contact_us_link,
                    $promoter_login_link,
                    $promoter_register_link
                );
            }
            elseif(!IS_WHITE_LABEL && !IS_PLUGIN_PAGE && !IS_BROWSE_PAGE && !IS_ROOT_PAGE && !($promoter->active) && ($customer->id != 0) && !($p_admin->proud == true || $p_customer->proud == true || $p_venue->proud == true))
            {

                $link = new stdClass();
                $link->title = 'Login';
                $link->_name = strtolower('Login');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'promoter/login/';

                $promoter_login_link = $link;

                $link = new stdClass();
                $link->title = 'Register';
                $link->_name = strtolower('Register');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'promoter/register/';

                $promoter_register_link = $link;

                $link = new stdClass();
                $link->title = 'Contact Us';
                $link->_name = strtolower('Contact Us');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'contact/';
                $contact_us_link = $link;

                $nav = array(
                    $p_news,
                    'promoter' => 'List your event',
                    $contact_us_link,
                    $promoter_login_link,
                    $promoter_register_link
                );

            }
            elseif(!IS_WHITE_LABEL  && !IS_PLUGIN_PAGE && !IS_BROWSE_PAGE && !IS_ROOT_PAGE && !($customer->active) && !($p_admin->proud == true || $p_promoter->proud == true || $p_venue->proud == true))
            {
                $link = new stdClass();
                $link->title = 'Login';
                $link->_name = strtolower('Login');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'customer/login/';

                $customer_login_link = $link;

                $link = new stdClass();
                $link->title = 'Register';
                $link->_name = strtolower('Register');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'customer/register/';

                $customer_register_link = $link;

                $link = new stdClass();
                $link->title = 'Contact Us';
                $link->_name = strtolower('Contact Us');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'contact/';
                $contact_us_link = $link;

                $nav = array(
                    $p_news,
                    'promoter' => 'List your event',
                    $contact_us_link,
                    $customer_login_link,
                    $customer_register_link,
                );

            }
            elseif(!IS_LOGGED_IN && $customer->active)
            {
                $link = new stdClass();
                $link->title = 'Login';
                $link->_name = strtolower('Login');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'customer/login/';

                $customer_login_link = $link;

                $link = new stdClass();
                $link->title = 'Register';
                $link->_name = strtolower('Register');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'customer/register/';

                $customer_register_link = $link;

                $link = new stdClass();
                $link->title = 'Contact Us';
                $link->_name = strtolower('Contact Us');
                $link->_id = mt_rand(955,999);
                $link->proud = false;
                $link->active = true;
                $link->url = URL.'contact/';
                $contact_us_link = $link;

                $nav = array(
                    $p_news,
                    'promoter' => 'List your event',
                    $contact_us_link,
                    $customer_login_link,
                    $customer_register_link,
                );
            }
                        
            // Menu
            if(!IS_WHITE_LABEL  && !IS_PLUGIN_PAGE) $b_nav->callback($this, 'e_menu', $nav);
            if(IS_HAT_TRICK_PRODUCTIONS)
            {                   
                $nav = array(
                    'Home' => 'http://www.demo.com/',                    
                    'About' => 'http://www.demo.com/',                    
                    'Shows' => 'http://www.demo.com/',                    
                    'Tickets' => WHITE_LABEL_URL,                    
                    'Distribution' => 'http://www.demointernational.com/hti/',                    
                    'Contact' => 'http://www.demo.com/',                    
                );

                if(IS_HAT_TRICK_PRODUCTIONS && IS_LOGGED_IN)
                {
                    $nav['My Account'] = WHITE_LABEL_URL . '/customer';
                    $nav['Logout'] = WHITE_LABEL_URL . '/customer/logout';
                }
                elseif(IS_HAT_TRICK_PRODUCTIONS && !IS_LOGGED_IN)
                {
                    $nav['Login'] = WHITE_LABEL_URL . '/customer/login';
                    $nav['Register'] = WHITE_LABEL_URL . '/customer/register';
                }
                if(IS_HAT_TRICK_PRODUCTIONS){
                    $custom_nav = $site->e_custom_menu($nav, 'custom-nav');
                    $b_nav->text($custom_nav);                    
                }else{
                    $site->e_custom_menu($nav, 'custom-nav');                
                }
            }

       