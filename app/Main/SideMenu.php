<?php

namespace App\Main;

class SideMenu
{
    /**
     * List of side menu items.
     */
    public static function menu()
    {
        $menu_leads = [
            'icon' => 'phone-call',
            'params' => [
            ],
            'title' => 'Leads Dashboard',
            'sub_menu' => [
                'home' => [
                    'icon' => 'home',
                    'route_name' => 'lead.dashboard',
                    'params' => [
                    ],
                    'title' => 'Dashboard',
                ],
                'leads' => [
                    'icon' => 'phone-forwarded',
                    'route_name' => 'lead.index',
                    'params' => [
                    ],
                    'title' => 'Leads',
                ],
                'campaign_dashboard-leads' => [
                    'icon' => 'align-justify',
                    'route_name' => 'lead.campaign',
                    'params' => [
                    ],
                    'title' => 'Campaign Dashboard',
                ],
                'jornaya-leads' => [
                    'icon' => 'server',
                    'route_name' => 'lead.jornaya',
                    'params' => [
                    ],
                    'title' => 'Jornaya ID',
                ],
                /*'log-leads' => [
                    'icon' => 'archive',
                    'route_name' => 'lead.log',
                    'params' => [
                    ],
                    'title' => 'Log Leads'
                ],
                'did-leads' => [
                    'icon' => 'aperture',
                    'route_name' => 'lead.did',
                    'params' => [
                    ],
                    'title' => 'DID Settings'
                ],*/
            ],
        ];

        $menu_adv = [
            'icon' => 'globe',
            'params' => [
            ],
            'title' => 'Adv Dashboard',
            'sub_menu' => [
                'dashboard' => [
                    'icon' => 'home',
                    'route_name' => 'dashboard.index',
                    'params' => [
                    ],
                    'title' => 'Dashboard',
                ],
                'advertisers' => [
                    'icon' => 'inbox',
                    'route_name' => 'advertisers.index',
                    'params' => [
                    ],
                    'title' => 'Accounts',
                ],
                'activities' => [
                    'icon' => 'calendar',
                    'route_name' => 'activities.index',
                    'params' => [
                    ],
                    'title' => 'Activity Log',
                ],
                'usermetrics' => [
                    'icon' => 'aperture',
                    'route_name' => 'usermetrics.index',
                    'params' => [
                    ],
                    'title' => 'User Metrics',
                ],
            ],
        ];

        $menu_rules = [
            'icon' => 'file-text',
            'params' => [
            ],
            'title' => 'Rules',
            'sub_menu' => [
                'list-rules' => [
                    'icon' => 'list',
                    'route_name' => 'rules.index',
                    'params' => [
                    ],
                    'title' => 'List Rules',
                ],
                'create-rules' => [
                    'icon' => 'plus-square',
                    'route_name' => 'rules.create',
                    'params' => [
                    ],
                    'title' => 'Create Rules',
                ],
            ],
        ];
        $menu_campaign = [
            'icon' => 'zoom-in',
            'params' => [
            ],
            'title' => 'Campaign Creator',
            'sub_menu' => [
                'campaign-management' => [
                    'icon' => 'megaphone',
                    'route_name' => 'campaigns.index',
                    'params' => [
                    ],
                    'title' => 'Campaign Management',
                ],
                'campaign-create' => [
                    'icon' => 'plus-circle',
                    'route_name' => 'campaigns.create',
                    'params' => [
                    ],
                    'title' => 'Campaign Creation',
                ],
            ],
        ];

        $menu_client = [
            'icon' => 'user',
            'route_name' => 'client.index',
            'params' => [
            ],
            'title' => 'Client Dashboard',
        ];

        // user permissions
        $menu = [];
        $menu['menu_leads'] = $menu_leads;

        return $menu;
    }
}
