<?php
namespace Themes\Oydisk;

use App\Models\File;
use App\Core\Database;
use App\Helpers\CoreHelper;
use App\Helpers\LanguageHelper;
use App\Helpers\ThemeHelper;
use App\Services\Theme;
use Themes\Oydisk\ThemeConfig;

class ThemeOydisk extends Theme
{
    public function __construct() {
        // load theme config
        $this->config = (new ThemeConfig())->getThemeConfig();
    }
    
    public function registerRoutes(\FastRoute\RouteCollector $r) {
        // register any theme routes
        $r->addRoute(['GET', 'POST'], '/'.ADMIN_FOLDER_NAME.'/theme_settings/'.$this->config['folder_name'], '\themes\\'.$this->config['folder_name'].'\controllers\admin\ThemeController/themeSettings');
        $r->addRoute(['GET', 'POST'], '/search', '\themes\\'.$this->config['folder_name'].'\controllers\IndexController/search');
        $r->addRoute(['GET', 'POST'], '/ajax/search', '\themes\\'.$this->config['folder_name'].'\controllers\IndexController/ajaxSearch');
        $r->addRoute(['GET'], '/faq', '\themes\\'.$this->config['folder_name'].'\controllers\IndexController/faq');
        $r->addRoute(['GET', 'POST'], '/upgrade[/{packageId:[0-9]+}]', '\themes\\'.$this->config['folder_name'].'\controllers\IndexController/upgrade');
        $r->addRoute(['GET', 'POST'], '/payment_complete', '\themes\\'.$this->config['folder_name'].'\controllers\IndexController/paymentComplete');
        $r->addRoute(['GET', 'POST'], '/contact', '\themes\\'.$this->config['folder_name'].'\controllers\IndexController/contact');
        $r->addRoute(['GET', 'POST'], '/report_file', '\themes\\'.$this->config['folder_name'].'\controllers\IndexController/reportFile');
        $r->addRoute(['GET', 'POST'], '/link_checker', '\themes\\'.$this->config['folder_name'].'\controllers\IndexController/linkChecker');
        $r->addRoute(['GET'], '/api', '\themes\\'.$this->config['folder_name'].'\controllers\IndexController/api');
		$r->addRoute(['GET', 'POST'], '/dmca', '\themes\\'.$this->config['folder_name'].'\controllers\IndexController/dmca');
        $r->addRoute(['GET', 'POST'], '/{shortUrl}~d', '\themes\\'.$this->config['folder_name'].'\controllers\FileController/fileDelete');
    }

    public function getThemeDetails() {
        return $this->config;
    }

    public function getThemeSkin() {
        $skin = ThemeHelper::getConfigValue('site_skin');
        if (strlen($skin)) {
            return $skin;
        }

        return false;
    }
    
    public function getFrontEndThemeSkin() {
        $skin = ThemeHelper::getConfigValue('front_end_site_skin');
        if (strlen($skin)) {
            return $skin;
        }

        return false;
    }

    public function outputCustomCSSCode() {
        // see if the file exists
        $localCachePath = CACHE_DIRECTORY_ROOT . '/themes/' . $this->config['folder_name'] . '/custom_css.css';
        if (file_exists($localCachePath)) {
            return "<link href=\"" . CACHE_WEB_ROOT . "/themes/" . $this->config['folder_name'] . "/custom_css.css?r=" . md5(microtime()) . "\" rel=\"stylesheet\">\n";
        }
    }

    public function getCustomCSSCode() {
        return ThemeHelper::getConfigValue('css_code');
    }

    public function outputHeadJSCode() {
        return $this->getHeadJSCode();
    }
    
    public function getHeadJSCode() {
        return ThemeHelper::getConfigValue('head_js_code');
    }

    public function getSimilarFiles(File $file) {
        $similarFiles = array();

        // load database
        $db = Database::getDatabase(true);

        // load orderby from session
        $orderBy = 'originalFilename';
        if (isset($_SESSION['search']['filterOrderBy'])) {
            $orderBy = $_SESSION['search']['filterOrderBy'];
        }
        
        // if this is a shared file (at file level), ensure we don't show the rest
        // of the folder files unless the user has access
        $isDirectSharedFile = false;
        if(isset($_SESSION['sharekeyFile' . $file->id])) {
            $isDirectSharedFile = true;
        }

        // get all other files in the same folder/album, only if this file is in an actual folder
        if ((int) $file->folderId) {
            $similarFiles = $db->getRows('SELECT * '
                    . 'FROM file '
                    . 'WHERE folderId = :folderId '
                    . 'AND status = "active" '
                    . 'ORDER BY ' . $this->convertSortOption($orderBy).' '
                    . 'LIMIT 200', array(
                'folderId' => (int) $file->folderId,
            ));
        }
        elseif ((int) $file->userId) {
            $similarFiles = $db->getRows('SELECT * '
                    . 'FROM file '
                    . 'WHERE userId = :userId '
                    . 'AND folderId IS NULL '
                    . 'AND status = "active" '
                    . 'ORDER BY ' . $this->convertSortOption($orderBy).' '
                    . 'LIMIT 200', array(
                'userId' => (int) $file->userId,
            ));
        }

        if (!is_array($similarFiles)) {
            return array();
        }

        if (!COUNT($similarFiles)) {
            return array();
        }

        // set the currently selected on as the first
        $startArr = array();
        $endArr = array();
        $found = false;
        $rsArr = array();
        foreach ($similarFiles AS $similarFile) {
            // double check the user is allowed access
            if($isDirectSharedFile === true) {
                if(!CoreHelper::getOverallPublicStatus($similarFile['userId'], $similarFile['folderId'], $similarFile['id'])) {
                    continue;
                }
            }
            
            // load the file object for the response
            $file = File::hydrateSingleRecord($similarFile);
            $rsArr[] = $file;
        }

        return $rsArr;
    }

    public function convertSortOption($filterOrderBy) {
        $sortColName = 'originalFilename asc';
        switch ($filterOrderBy) {
            case 'order_by_filename_asc':
                $sortColName = 'originalFilename asc';
                break;
            case 'order_by_filename_desc':
                $sortColName = 'originalFilename desc';
                break;
            case 'order_by_uploaded_date_asc':
            case '':
                $sortColName = 'uploadedDate asc';
                break;
            case 'order_by_uploaded_date_desc':
                $sortColName = 'uploadedDate desc';
                break;
            case 'order_by_downloads_asc':
                $sortColName = 'visits asc';
                break;
            case 'order_by_downloads_desc':
                $sortColName = 'visits desc';
                break;
            case 'order_by_filesize_asc':
                $sortColName = 'fileSize asc';
                break;
            case 'order_by_filesize_desc':
                $sortColName = 'fileSize desc';
                break;
            case 'order_by_last_access_date_asc':
                $sortColName = 'lastAccessed asc';
                break;
            case 'order_by_last_access_date_desc':
                $sortColName = 'lastAccessed desc';
                break;
        }

        return $sortColName;
    }

    public function outputSuccess() {
        $html = '';
        $html .= "<script>\n";
        $html .= "$(document).ready(function() {\n";
        $success = notification::getSuccess();
        if (COUNT($success)) {
            $htmlArr = array();
            foreach ($success AS $success) {
                $htmlArr[] = $success;
            }

            $msg = implode("<br/>", $htmlArr);
        }
        $html .= "showSuccessNotification('" . str_replace('\'', '', TranslateHelper::t('success', 'Success')) . "', '" . str_replace('\'', '', $msg) . "');\n";
        $html .= "});\n";
        $html .= "</script>\n";

        return $html;
    }

    public function outputErrors() {
        $html = '';
        $html .= "<script>\n";
        $html .= "$(document).ready(function() {\n";
        $errors = notification::getErrors();
        if (COUNT($errors)) {
            $htmlArr = array();
            foreach ($errors AS $error) {
                $htmlArr[] = $error;
            }

            $msg = implode("<br/>", $htmlArr);
        }
        $html .= "showErrorNotification('" . str_replace('\'', '', TranslateHelper::t('error', 'Error')) . "', '" . str_replace('\'', '', $msg) . "');\n";
        $html .= "});\n";
        $html .= "</script>\n";

        return $html;
    }

    public function getAccountWebRoot() {
        return ACCOUNT_WEB_ROOT;
    }

    public function getAccountCssPath() {
        return SITE_THEME_PATH . '/assets/styles';
    }

    public function getAccountJsPath() {
        return SITE_THEME_PATH . '/assets/js';
    }

    public function getAccountImagePath() {
        return SITE_THEME_PATH . '/assets/images';
    }
    
    public function getFrontendWebRoot() {
        return WEB_ROOT;
    }

    public function getFrontendCssPath() {
        return SITE_THEME_PATH . '/assets/frontend/css';
    }

    public function getFrontendJsPath() {
        return SITE_THEME_PATH . '/assets/frontend/js';
    }

    public function getFrontendImagePath() {
        return SITE_THEME_PATH . '/assets/frontend/img';
    }
public function getOyDiskCssPath() {
        return SITE_THEME_PATH . '/assets/css';
    }

    public function getOyDiskJsPath() {
        return SITE_THEME_PATH . '/assets/js';
    }

    public function getOyDiskImgPath() {
        return SITE_THEME_PATH . '/assets/img';
    }
    public function getActiveLanguages() {
        // return active languages
        return LanguageHelper::getActiveLanguages();
    }
    
    public function getActiveLabel() {
        // return active language label
        return LanguageHelper::getActiveLabel();
    }
    
    public function getActiveFlag() {
        // return active language flag
        return LanguageHelper::getActiveFlag();
    }
    
    public function isPng($filePath)
    {
        // get first 4 characters for validation
        $handle = fopen($filePath, 'r');
        $bytes = strtoupper(bin2hex(fread($handle, 4)));
        fclose($handle);

        return $bytes === '89504E47';
    }
}
