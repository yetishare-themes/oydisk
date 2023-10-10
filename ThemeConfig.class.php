<?php

namespace Themes\Oydisk;

use App\Services\ThemeConfig AS CoreThemeConfig;

class ThemeConfig extends CoreThemeConfig
{
    /**
     * Setup the theme config.
     *
     * @var array
     */
    public $config = array(
        'theme_name' => 'Oydisk Theme',
        'folder_name' => 'oydisk',
        'theme_description' => 'Theme for the Yetishare file hosting script.',
        'author_name' => 'YetishareThemes',
        'author_website' => 'https://yetishare-themes.com',
        'theme_version' => '1.0',
        'required_script_version' => '5.0',
        'product' => 'file_hosting',
        'product_name' => 'Yetishare',
        'product_url' => 'https://yetishare.com',
    );

}
