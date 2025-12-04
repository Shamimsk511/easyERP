<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    |
    | Here you can change the default title of your admin panel.
    |
    | For detailed instructions you can look the title section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'title' => 'EasyErp',
    'title_prefix' => '',
    'title_postfix' => '',

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    |
    | Here you can activate the favicon.
    |
    | For detailed instructions you can look the favicon section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_ico_only' => false,
    'use_full_favicon' => false,

    /*
    |--------------------------------------------------------------------------
    | Google Fonts
    |--------------------------------------------------------------------------
    |
    | Here you can allow or not the use of external google fonts. Disabling the
    | google fonts may be useful if your admin panel internet access is
    | restricted somehow.
    |
    | For detailed instructions you can look the google fonts section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'google_fonts' => [
        'allowed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Logo
    |--------------------------------------------------------------------------
    |
    | Here you can change the logo of your admin panel.
    |
    | For detailed instructions you can look the logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'logo' => '<b>EASY</b>ERP',
    'logo_img' => 'vendor/adminlte/dist/img/AdminLTELogo.png',
    'logo_img_class' => 'brand-image img-circle elevation-3',
    'logo_img_xl' => null,
    'logo_img_xl_class' => 'brand-image-xs',
    'logo_img_alt' => 'Admin Logo',

    /*
    |--------------------------------------------------------------------------
    | Authentication Logo
    |--------------------------------------------------------------------------
    |
    | Here you can setup an alternative logo to use on your login and register
    | screens. When disabled, the admin panel logo will be used instead.
    |
    | For detailed instructions you can look the auth logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'auth_logo' => [
        'enabled' => false,
        'img' => [
            'path' => 'vendor/adminlte/dist/img/AdminLTELogo.png',
            'alt' => 'Auth Logo',
            'class' => '',
            'width' => 50,
            'height' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preloader Animation
    |--------------------------------------------------------------------------
    |
    | Here you can change the preloader animation configuration. Currently, two
    | modes are supported: 'fullscreen' for a fullscreen preloader animation
    | and 'cwrapper' to attach the preloader animation into the content-wrapper
    | element and avoid overlapping it with the sidebars and the top navbar.
    |
    | For detailed instructions you can look the preloader section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'preloader' => [
        'enabled' => false,
        'mode' => 'fullscreen',
        'img' => [
            'path' => 'vendor/adminlte/dist/img/AdminLTELogo.png',
            'alt' => 'AdminLTE Preloader Image',
            'effect' => 'animation__shake',
            'width' => 60,
            'height' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Menu
    |--------------------------------------------------------------------------
    |
    | Here you can activate and change the user menu.
    |
    | For detailed instructions you can look the user menu section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'usermenu_enabled' => true,
    'usermenu_header' => false,
    'usermenu_header_class' => 'bg-primary',
    'usermenu_image' => false,
    'usermenu_desc' => false,
    'usermenu_profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Here we change the layout of your admin panel.
    |
    | For detailed instructions you can look the layout section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'layout_topnav' => null,
    'layout_boxed' => null,
    'layout_fixed_sidebar' => null,
    'layout_fixed_navbar' => null,
    'layout_fixed_footer' => null,
    'layout_dark_mode' => null,

    /*
    |--------------------------------------------------------------------------
    | Authentication Views Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the authentication views.
    |
    | For detailed instructions you can look the auth classes section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_auth_card' => 'card-outline card-primary',
    'classes_auth_header' => '',
    'classes_auth_body' => '',
    'classes_auth_footer' => '',
    'classes_auth_icon' => '',
    'classes_auth_btn' => 'btn-flat btn-primary',

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the admin panel.
    |
    | For detailed instructions you can look the admin panel classes here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_body' => '',
    'classes_brand' => '',
    'classes_brand_text' => '',
    'classes_content_wrapper' => '',
    'classes_content_header' => '',
    'classes_content' => '',
    'classes_sidebar' => 'sidebar-dark-primary elevation-4',
    'classes_sidebar_nav' => '',
    'classes_topnav' => 'navbar-white navbar-light',
    'classes_topnav_nav' => 'navbar-expand',
    'classes_topnav_container' => 'container',

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar of the admin panel.
    |
    | For detailed instructions you can look the sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'sidebar_mini' => 'lg',
    'sidebar_collapse' => false,
    'sidebar_collapse_auto_size' => false,
    'sidebar_collapse_remember' => false,
    'sidebar_collapse_remember_no_transition' => true,
    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'l',
    'sidebar_nav_accordion' => true,
    'sidebar_nav_animation_speed' => 300,

    /*
    |--------------------------------------------------------------------------
    | Control Sidebar (Right Sidebar)
    |--------------------------------------------------------------------------
    |
    | Here we can modify the right sidebar aka control sidebar of the admin panel.
    |
    | For detailed instructions you can look the right sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'right_sidebar' => false,
    'right_sidebar_icon' => 'fas fa-cogs',
    'right_sidebar_theme' => 'dark',
    'right_sidebar_slide' => true,
    'right_sidebar_push' => true,
    'right_sidebar_scrollbar_theme' => 'os-theme-light',
    'right_sidebar_scrollbar_auto_hide' => 'l',

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    |
    | Here we can modify the url settings of the admin panel.
    |
    | For detailed instructions you can look the urls section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_route_url' => false,
    'dashboard_url' => '/',
    'logout_url' => 'logout',
    'login_url' => 'login',
    'register_url' => 'register',
    'password_reset_url' => 'password/reset',
    'password_email_url' => 'password/email',
    'profile_url' => false,
    'disable_darkmode_routes' => false,

    /*
    |--------------------------------------------------------------------------
    | Laravel Asset Bundling
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Laravel Asset Bundling option for the admin panel.
    | Currently, the next modes are supported: 'mix', 'vite' and 'vite_js_only'.
    | When using 'vite_js_only', it's expected that your CSS is imported using
    | JavaScript. Typically, in your application's 'resources/js/app.js' file.
    | If you are not using any of these, leave it as 'false'.
    |
    | For detailed instructions you can look the asset bundling section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'laravel_asset_bundling' => false,
    'laravel_css_path' => 'css/app.css',
    'laravel_js_path' => 'js/app.js',

    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar/top navigation of the admin panel.
    |
    | For detailed instructions you can look here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

'menu' => [
    // Navbar items:
    [
        'type' => 'fullscreen-widget',
        'topnav_right' => true,
    ],

    // Sidebar items:
    [
        'type' => 'sidebar-menu-search',
        'text' => 'search',
    ],

    // Dashboard
    [
        'text' => 'Dashboard',
        'url' => '/',
        'icon' => 'fas fa-tachometer-alt',
        'icon_color' => 'primary',
    ],

    // Accounting Section
    ['header' => 'ACCOUNTING & FINANCE'],
    [
        'text' => 'Chart of Accounts',
        'url' => 'accounts',
        'icon' => 'fas fa-university',
        'icon_color' => 'info',
    ],
    [
        'text' => 'Transactions',
        'icon' => 'fas fa-exchange-alt',
        'icon_color' => 'success',
        'submenu' => [
            [
                'text' => 'All Transactions',
                'url' => 'transactions',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'New Transaction',
                'url' => 'transactions/create',
                'icon' => 'far fa-circle',
            ],
        ],
    ],
    [
        'text' => 'Reports',
        'icon' => 'fas fa-chart-line',
        'icon_color' => 'danger',
        'submenu' => [
            [
                'text' => 'Trial Balance',
                'url' => 'reports/trial-balance',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Balance Sheet',
                'url' => 'reports/balance-sheet',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Profit & Loss',
                'url' => 'reports/profit-loss',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Cash Flow',
                'url' => 'reports/cash-flow',
                'icon' => 'far fa-circle',
            ],
        ],
    ],

    // Inventory Management
    ['header' => 'INVENTORY MANAGEMENT'],
    [
        'text' => 'Products',
        'icon' => 'fas fa-boxes',
        'icon_color' => 'teal',
        'submenu' => [
            [
                'text' => 'All Products',
                'url' => 'products',
                'icon' => 'fas fa-box',
            ],
            [
                'text' => 'Product Groups',
                'url' => 'product-groups',
                'icon' => 'fas fa-layer-group',
            ],
            [
                'text' => 'Units of Measure',
                'url' => 'units',
                'icon' => 'fas fa-ruler-combined',
            ],
        ],
    ],
    [
        'text' => 'Stock Reports',
        'icon' => 'fas fa-warehouse',
        'icon_color' => 'indigo',
        'submenu' => [
            [
                'text' => 'Stock Summary',
                'url' => 'reports/stock-summary',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Low Stock Alert',
                'url' => 'reports/low-stock',
                'icon' => 'far fa-circle',
                'label' => 'Alert',
                'label_color' => 'warning',
            ],
            [
                'text' => 'Stock Valuation',
                'url' => 'reports/stock-valuation',
                'icon' => 'far fa-circle',
            ],
        ],
    ],

    // Purchase Management
    ['header' => 'PURCHASE MANAGEMENT'],
    [
        'text' => 'Vendors',
        'icon' => 'fas fa-truck',
        'icon_color' => 'purple',
        'submenu' => [
            [
                'text' => 'All Vendors',
                'route' => 'vendors.index',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Add Vendor',
                'route' => 'vendors.create',
                'icon' => 'far fa-circle',
            ],
        ],
    ],
    [
        'text' => 'Purchase Orders',
        'icon' => 'fas fa-shopping-cart',
        'icon_color' => 'orange',
        'submenu' => [
            [
                'text' => 'All Orders',
                'route' => 'purchase-orders.index',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Create Order',
                'route' => 'purchase-orders.create',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Pending Orders',
                'url' => 'purchase-orders?status=pending',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Received Orders',
                'url' => 'purchase-orders?status=received',
                'icon' => 'far fa-circle',
            ],
        ],
    ],
    [
        'text' => 'Purchase Reports',
        'icon' => 'fas fa-file-invoice-dollar',
        'icon_color' => 'brown',
        'submenu' => [
            [
                'text' => 'Purchase Register',
                'url' => 'reports/purchase-register',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Vendor Wise',
                'url' => 'reports/vendor-wise-purchase',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Outstanding Payables',
                'url' => 'reports/payables',
                'icon' => 'far fa-circle',
            ],
        ],
    ],

    // Sales Management
    ['header' => 'SALES MANAGEMENT'],
    [
        'text' => 'Customers',
        'icon' => 'fas fa-users',
        'icon_color' => 'pink',
        'submenu' => [
            [
                'text' => 'All Customers',
                'route' => 'customers.index',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Add Customer',
                'route' => 'customers.create',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Customer Groups',
                'route' => 'customer-groups.index',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Overdue Customers',
                'url' => 'customers?overdue=1',
                'icon' => 'far fa-circle',
                'label' => 'Alert',
                'label_color' => 'danger',
            ],
        ],
    ],
    [
        'text' => 'Sales Orders',
        'icon' => 'fas fa-file-invoice',
        'icon_color' => 'cyan',
        'submenu' => [
            [
                'text' => 'All Sales',
                'url' => 'sales',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'New Sale',
                'url' => 'sales/create',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Pending Sales',
                'url' => 'sales?status=pending',
                'icon' => 'far fa-circle',
            ],
        ],
    ],
    [
        'text' => 'Sales Reports',
        'icon' => 'fas fa-chart-bar',
        'icon_color' => 'lime',
        'submenu' => [
            [
                'text' => 'Sales Register',
                'url' => 'reports/sales-register',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Customer Wise',
                'url' => 'reports/customer-wise-sales',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Outstanding Receivables',
                'url' => 'reports/receivables',
                'icon' => 'far fa-circle',
            ],
        ],
    ],

    // Vouchers Section
    ['header' => 'VOUCHERS & PAYMENTS'],
    [
        'text' => 'Payment Vouchers',
        'icon' => 'fas fa-money-bill-wave',
        'icon_color' => 'green',
        'submenu' => [
            [
                'text' => 'Payment',
                'url' => 'vouchers/payment',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Receipt',
                'url' => 'vouchers/receipt',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Contra',
                'url' => 'vouchers/contra',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Journal',
                'url' => 'vouchers/journal',
                'icon' => 'far fa-circle',
            ],
        ],
    ],

    // Settings
    ['header' => 'SETTINGS'],
    [
        'text' => 'Company Settings',
        'url' => 'settings/company',
        'icon' => 'fas fa-building',
        'icon_color' => 'gray',
    ],
    [
        'text' => 'User Management',
        'icon' => 'fas fa-users-cog',
        'icon_color' => 'dark',
        'can' => 'manage-users',
        'submenu' => [
            [
                'text' => 'Users',
                'url' => 'users',
                'icon' => 'far fa-circle',
            ],
            [
                'text' => 'Roles & Permissions',
                'url' => 'roles',
                'icon' => 'far fa-circle',
            ],
        ],
    ],
    [
        'text' => 'System Settings',
        'url' => 'settings/system',
        'icon' => 'fas fa-cog',
        'icon_color' => 'secondary',
        'can' => 'manage-settings',
    ],
    [
        'text' => 'Backup & Restore',
        'url' => 'settings/backup',
        'icon' => 'fas fa-database',
        'icon_color' => 'navy',
        'can' => 'manage-settings',
    ],
],


    /*
    |--------------------------------------------------------------------------
    | Menu Filters
    |--------------------------------------------------------------------------
    |
    | Here we can modify the menu filters of the admin panel.
    |
    | For detailed instructions you can look the menu filters section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins Initialization
    |--------------------------------------------------------------------------
    |
    | Here we can modify the plugins used inside the admin panel.
    |
    | For detailed instructions you can look the plugins section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Plugins-Configuration
    |
    */

    'plugins' => [
        'Datatables' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => '//cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => '//cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css',
                ],
            ],
        ],
        'Select2' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.css',
                ],
            ],
        ],
        'Chartjs' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.bundle.min.js',
                ],
            ],
        ],
        'Sweetalert2' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => '//cdn.jsdelivr.net/npm/sweetalert2@11',
                ],
            ],
        ],
        'Pace' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/themes/blue/pace-theme-center-radar.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.min.js',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IFrame
    |--------------------------------------------------------------------------
    |
    | Here we change the IFrame mode configuration. Note these changes will
    | only apply to the view that extends and enable the IFrame mode.
    |
    | For detailed instructions you can look the iframe mode section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/IFrame-Mode-Configuration
    |
    */

    'iframe' => [
        'default_tab' => [
            'url' => null,
            'title' => null,
        ],
        'buttons' => [
            'close' => true,
            'close_all' => true,
            'close_all_other' => true,
            'scroll_left' => true,
            'scroll_right' => true,
            'fullscreen' => true,
        ],
        'options' => [
            'loading_screen' => 1000,
            'auto_show_new_tab' => true,
            'use_navbar_items' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Livewire support.
    |
    | For detailed instructions you can look the livewire here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'livewire' => false,
];
