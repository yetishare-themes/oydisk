<?php

namespace Themes\Oydisk\Controllers;

use App\Controllers\IndexController AS CoreIndexController;
use App\Core\Database;
use App\Models\File;
use App\Models\FileReport;
use App\Models\User;
use App\Helpers\ApiV2Helper;
use App\Helpers\AuthHelper;
use App\Helpers\CoreHelper;
use App\Helpers\FileHelper;
use App\Helpers\NotificationHelper;
use App\Helpers\PluginHelper;
use App\Helpers\TranslateHelper;
use App\Helpers\UserHelper;
use App\Helpers\ValidationHelper;

class IndexController extends CoreIndexController
{

    public function index() {
        // direct to install if it exists still
        if (file_exists(DOC_ROOT . '/install')) {
            return $this->redirect(WEB_ROOT . '/install');
        }

        // load template
        return $this->render('index.html');
    }
public function dmca() {
    // load template
    return $this->render('dmca.html', array(
        'indexVariable' => 'Dmca',
    ));
}
    public function faq() {
        // prepare template variables
        $acceptedFileTypes = UserHelper::getAcceptedFileTypes();

        // load template
        return $this->render('faq.html', array(
                    'maxUploadSizeFreeAcc' => CoreHelper::formatSize(UserHelper::getMaxUploadFilesize(1), 'both', true, 0),
                    'maxUploadSizePaidAcc' => CoreHelper::formatSize(UserHelper::getMaxUploadFilesize(2), 'both', true, 0),
                    'acceptedFileTypes' => $acceptedFileTypes,
                    'fileTypesStr' => ((count($acceptedFileTypes)) ? str_replace('.', '', implode(', ', $acceptedFileTypes)) : TranslateHelper::t('faq_any', 'Any')),
                    'fileRemovalFreeAcc' => (int) UserHelper::getDaysToKeepInactiveFiles(1) == 0 ? TranslateHelper::t('faq_unlimited', 'unlimited') : UserHelper::getDaysToKeepInactiveFiles(1),
                    'fileRemovalPaidAcc' => (int) UserHelper::getDaysToKeepInactiveFiles(2) == 0 ? TranslateHelper::t('faq_unlimited', 'unlimited') : UserHelper::getDaysToKeepInactiveFiles(2),
        ));
    }

    public function upgrade($packageId = null) {
        // require login
        if (UserHelper::enableUpgradePage() == 'no') {
            // disable
            return $this->redirect(WEB_ROOT);
        }

        // load libraries for later
        $Auth = AuthHelper::getAuth();
        $db = Database::getDatabase();

        // load all paid packages if no $packageId
        $template = 'upgrade';
        $pricingRows = array();
        $premiumPackages = array();
        if ($packageId === null) {
            $premiumPackages = $db->getRows('SELECT * '
                    . 'FROM user_level '
                    . 'WHERE on_upgrade_page = 1 '
                    . 'ORDER BY level_type=\'free\' DESC, level_type=\'paid\' DESC, id ASC');
            if (count($premiumPackages) === 0) {
                return $this->redirect(CoreHelper::getCoreSitePath() . "/error?e=" . urlencode('No packages found, please contact the support team. (at least 1 package needs to have the option of "On Upgrade Page" to "Yes" with "Package Type" of "Paid")'));
            }

            // if only 1, redirect to only the upgrade page
            if (count($premiumPackages) === 1) {
                foreach ($premiumPackages AS $premiumPackage) {
                    return $this->redirect(WEB_ROOT . '/upgrade/' . $premiumPackage['id']);
                }
            }

            // otherwise, show all packages
            $template = 'upgrade_by_package';
            $colPercentage = floor(100 / count($premiumPackages));

            // prepare for output
            foreach ($premiumPackages AS $k => $premiumPackage) {
                // assume most popular is the 2nd from last. This can be amended as needed
                $mostPopular = false;
                if (($k + 1) == count($premiumPackages) - 1) {
                    $mostPopular = true;
                }

                $pricePerMonth = strtoupper(TranslateHelper::t('free', 'free'));
                $period = '1M';
                switch ($premiumPackage['level_type']) {
                    case 'free':
                        // different button for logged in users
                        if ($Auth->loggedIn()) {
                            $pricingButton = array(
                                'url' => CoreHelper::getCoreSitePath() . '/account',
                                'label' => TranslateHelper::t('your_account', 'Your Account'),
                            );
                        }
                        else {
                            $pricingButton = array(
                                'url' => CoreHelper::getCoreSitePath() . '/register',
                                'label' => TranslateHelper::t('register_now', 'Register Now'),
                            );
                        }
                        break;
                    case 'paid':
                        // load all prices
                        $prices = $db->getRows('SELECT id, pricing_label, period, price '
                                . 'FROM user_level_pricing '
                                . 'WHERE user_level_id = :level_id '
                                . 'ORDER BY price ASC', array(
                            'level_id' => (int) $premiumPackage['level_id'],
                        ));
                        if (count($prices) > 0) {
                            // get lowest price
                            $lowest = null;
                            foreach ($prices AS $price) {
                                if ($lowest !== null) {
                                    continue;
                                }
                                $lowest = $price['price'];
                                $period = $price['period'];
                            }
                            $pricePerMonth = $lowest;
                        }

                        $pricingButton = array(
                            'url' => CoreHelper::getCoreSitePath() . '/upgrade/' . $premiumPackage['id'],
                            'label' => $Auth->loggedIn() ? TranslateHelper::t('upgrade_now', 'Upgrade Now') : TranslateHelper::t('signup_now', 'Signup Now'),
                        );

                        break;
                }

                $premiumPackages[$k]['_price_per_month'] = $pricePerMonth;
                $premiumPackages[$k]['_price_per_period'] = $period;
                $premiumPackages[$k]['_hd_storage'] = (int) $premiumPackage['max_storage_bytes'] == 0 ? UCWords(TranslateHelper::t('unlimited', 'unlimited')) : CoreHelper::formatSize($premiumPackage['max_storage_bytes']);
                $premiumPackages[$k]['_pricing_button'] = $pricingButton;
                $premiumPackages[$k]['_is_most_popular'] = $mostPopular;
            }
        }
        else {
            // shown on upgrade page, same page as payment gateways
            // load all prices
            $pricingRows = $db->getRows('SELECT id, pricing_label, period, price, package_pricing_type, download_allowance '
                    . 'FROM user_level_pricing '
                    . 'WHERE user_level_id = :user_level_id '
                    . 'ORDER BY price ASC', array(
                'user_level_id' => $packageId,
            ));
            if (count($pricingRows) === 0) {
                return $this->redirect(CoreHelper::getCoreSitePath() . "/error?e=" . urlencode('No pricing items found, please contact the support team. (at least 1 pricing needs to be configured via the script admin area)'));
            }

            // loop and prepare for view
            foreach ($pricingRows AS $k => $pricingRow) {
                $pricingRows[$k]['_days'] = 0;
                if ($pricingRow['package_pricing_type'] === 'period') {
                    $pricingRows[$k]['_days'] = CoreHelper::convertStringDatePeriodToDays($pricingRow['period']);
                }
                $pricingRows[$k]['_label'] = TranslateHelper::t(ValidationHelper::removeInvalidCharacters(str_replace(' ', '_', strtolower($pricingRow['pricing_label'])), 'abcdefghijklmnopqrstuvwxyz12345678900'), $pricingRow['pricing_label']);

                $pricingRows[$k]['_per_day_cost'] = 0;
                if ($pricingRows[$k]['_days'] > 0) {
                    $pricingRows[$k]['_per_day_cost'] = number_format(str_replace(",", "", $pricingRow['price']) / $pricingRows[$k]['_days'], 2);
                }
            }
            $colPercentage = floor(100 / count($pricingRows));
        }

        // call plugin hooks, for redirect types (only processes the first plugin found)
        PluginHelper::callHook('upgradePageProcess');

        // prepare template parameters
        $pageTitlePrepend = ucwords(TranslateHelper::t('premium', 'premium'));
        if ($Auth->level_id > 0) {
            $pageTitlePrepend = ucwords(TranslateHelper::t('upgrade', 'upgrade'));
        }

        // load current user type
        $accountType = $db->getValue('SELECT level_type '
                . 'FROM user_level '
                . 'WHERE id = :id '
                . 'LIMIT 1', array(
            'id' => $Auth->level_id,
        ));

        // get user account paid details
        $user = User::loadOneById($Auth->id);
        $accountExpiry = (in_array($accountType, array('admin', 'moderator'))) ? ucwords(TranslateHelper::t('never', 'never')) : CoreHelper::formatDate($user->paidExpiryDate);

        // plugins
        $upgradePagePluginMiddle = PluginHelper::callHookRecursive('upgradePageMiddle');
        ksort($upgradePagePluginMiddle);

        // load template
        return $this->render($template . '.html', array(
                    'pageTitlePrepend' => $pageTitlePrepend,
                    'user' => $user,
                    'premiumPackages' => $premiumPackages,
                    'pricingRows' => $pricingRows,
                    'pricingColSizePercent' => $colPercentage,
                    'packageLevelId' => $packageId,
                    'upgradeDays' => array(7, 30, 90, 180, 365),
                    'accountType' => $accountType,
                    'accountExpiry' => $accountExpiry,
                    'accountTypeLabel' => TranslateHelper::t('account_type_' . str_replace(' ', '_', $Auth->level), ucwords($Auth->level)),
                    'upgradePagePluginMiddle' => $upgradePagePluginMiddle,
        ));
    }

    public function paymentComplete() {
        // load template
        return $this->render('payment_complete.html');
    }

    public function contact() {
        // load libraries for later
        $Auth = AuthHelper::getAuth();
        $db = Database::getDatabase();
        $request = $this->getRequest();

        $queryTypes = array(
            'contact_query_type_site_support' => TranslateHelper::t('contact_query_type_site_support', 'Site Support'),
            'contact_query_type_bug_report' => TranslateHelper::t('contact_query_type_bug_report', 'Bug Report'),
            'contact_query_type_abuse_report' => TranslateHelper::t('contact_query_type_abuse_report', 'Abuse Report'),
            'contact_query_type_suggest_improvements' => TranslateHelper::t('contact_query_type_suggest_improvements', 'Suggest Improvement'),
            'contact_query_type_other' => TranslateHelper::t('contact_query_type_other', 'Other'),
        );

        // prepare variables
        $fullName = '';
        $emailAddress = '';
        $queryType = '';
        $query = '';

        // send report if submitted
        if ($request->request->has('submitme')) {
            $fullName = trim($request->request->get('fullName'));
            $emailAddress = strtolower(trim($request->request->get('emailAddress')));
            $queryType = trim($request->request->get('queryType'));
            $query = trim($request->request->get('query'));

            if (strlen($fullName) == 0) {
                NotificationHelper::setError(TranslateHelper::t("contact_error_name", "Please enter your name."));
            }
            elseif (strlen($emailAddress) == 0) {
                NotificationHelper::setError(TranslateHelper::t("contact_error_email", "Please enter your email."));
            }
            elseif (ValidationHelper::validEmail($emailAddress) == false) {
                NotificationHelper::setError(TranslateHelper::t("contact_error_email_invalid", "Please enter a valid email address."));
            }
            elseif (strlen($queryType) == 0) {
                NotificationHelper::setError(TranslateHelper::t("contact_error_query_type", "Please select your type of query."));
            }
            elseif (strlen($query) == 0) {
                NotificationHelper::setError(TranslateHelper::t("contact_error_signature", "Please enter your query."));
            }

            // check captcha
            if ((!NotificationHelper::isErrors()) && (SITE_CONFIG_CONTACT_FORM_SHOW_CAPTCHA == 'yes')) {
                $resp = CoreHelper::captchaCheck();
                if ($resp == false) {
                    NotificationHelper::setError(TranslateHelper::t("invalid_captcha", "Captcha confirmation text is invalid."));
                }
            }

            // add to database and send email to admin
            if (NotificationHelper::isErrors() == false) {
                // send email
                $loggedInUsername = $Auth->loggedIn() ? $Auth->username : 'Guest';
                $replacements = array(
                    'FULL_NAME' => $fullName,
                    'EMAIL_ADDRESS' => $emailAddress,
                    'QUERY_TYPE' => $queryTypes[$queryType],
                    'QUERY' => nl2br($query),
                    'SITE_NAME' => SITE_CONFIG_SITE_NAME,
                    'WEB_ROOT' => WEB_ROOT,
                    'LOGGED_IN' => $Auth->loggedIn() ? 'Yes' : 'No',
                    'LOGGED_IN_USERNAME' => $loggedInUsername,
                    'USERS_IP' => CoreHelper::getUsersIPAddress()
                );

                $subject = TranslateHelper::t('contact_email_subject_v2', '"[[[QUERY_TYPE]]]" contact from [[[SITE_NAME]]] by "[[[LOGGED_IN_USERNAME]]]" user.', $replacements);
                $defaultContent = "There has been a contact form submission from [[[SITE_NAME]]] with the following details:<br/><br/>";
                $defaultContent .= "***************************************<br/>";
                $defaultContent .= "Full Name: [[[FULL_NAME]]]<br/>";
                $defaultContent .= "Email Address: [[[EMAIL_ADDRESS]]]<br/>";
                $defaultContent .= "Query Type: [[[QUERY_TYPE]]]<br/>";
                $defaultContent .= "<br/>[[[QUERY]]]<br/>";
                $defaultContent .= "***************************************<br/>";
                $defaultContent .= "Logged In: [[[LOGGED_IN]]]<br/>";
                $defaultContent .= "Username: [[[LOGGED_IN_USERNAME]]]<br/>";
                $defaultContent .= "Submitted IP: [[[USERS_IP]]]<br/>";
                $defaultContent .= "***************************************<br/><br/>";
                $htmlMsg = TranslateHelper::t('contact_email_content_v2', $defaultContent, $replacements);

                CoreHelper::sendHtmlEmail(SITE_CONFIG_SITE_CONTACT_FORM_EMAIL, $subject, $htmlMsg, SITE_CONFIG_DEFAULT_EMAIL_ADDRESS_FROM, strip_tags(str_replace("<br/>", "\n", $htmlMsg)), false, $fullName, true, $emailAddress);

                return $this->redirect(WEB_ROOT . '/contact?s=1');
            }
        }
        else {
            // pickup account details for pre-filling form
            if ($Auth->loggedIn()) {
                $fullName = $Auth->user->firstname . ' ' . $Auth->user->lastname;
                $emailAddress = $Auth->user->email;
            }
        }

        // success handling
        if ($request->query->has('s')) {
            NotificationHelper::setSuccess(TranslateHelper::t('contact_success', 'Thanks for submitting the contact form on our site. We\'ll review the query as soon as possible and get back to your within the next 48 hours.'));
        }

        // load template
        return $this->render('contact.html', array(
                    'fullName' => $fullName,
                    'emailAddress' => $emailAddress,
                    'queryType' => $queryType,
                    'query' => $query,
                    'queryTypes' => $queryTypes,
        ));
    }

    public function reportFile() {
        // load libraries for later
        $Auth = AuthHelper::getAuth();
        $db = Database::getDatabase();
        $request = $this->getRequest();

        // prepare variables
        $fileUrl = '';
        $otherInformation = '';
        $reportedByName = '';
        $reportedByEmail = '';
        $reportedByAddress = '';
        $reportedByTelephoneNumber = '';
        $digitalSignature = '';
        $confirm1 = '';
        $confirm2 = '';

        // send report if submitted
        if ($request->request->has('submitme')) {
            $fileUrl = trim($request->request->get('fileUrl'));
            $otherInformation = trim($request->request->get('otherInformation'));
            $reportedByName = trim($request->request->get('reportedByName'));
            $reportedByEmail = strtolower(trim($request->request->get('reportedByEmail')));
            $reportedByAddress = trim($request->request->get('reportedByAddress'));
            $reportedByTelephoneNumber = trim($request->request->get('reportedByTelephoneNumber'));
            $digitalSignature = trim($request->request->get('digitalSignature'));
            $confirm1 = trim($request->request->get('confirm1'));
            $confirm2 = trim($request->request->get('confirm2'));

            if (strlen($fileUrl) == 0) {
                NotificationHelper::setError(TranslateHelper::t("report_abuse_error_no_url", "Please enter the url of the file you're reporting."));
            }
            elseif (strlen($otherInformation) == 0) {
                NotificationHelper::setError(TranslateHelper::t("report_abuse_error_description", "Please enter the description and support information of the reported file."));
            }
            elseif (strlen($reportedByName) == 0) {
                NotificationHelper::setError(TranslateHelper::t("report_abuse_error_name", "Please enter your name."));
            }
            elseif (strlen($reportedByEmail) == 0) {
                NotificationHelper::setError(TranslateHelper::t("report_abuse_error_email", "Please enter your email."));
            }
            elseif (strlen($digitalSignature) == 0) {
                NotificationHelper::setError(TranslateHelper::t("report_abuse_error_signature", "Please provide the electronic signature of yourself or the copyright owner."));
            }
            elseif ($confirm1 != 'yes') {
                NotificationHelper::setError(TranslateHelper::t("report_abuse_error_confirm_1", "Please confirm you have a good faith belief that use of the material in the manner complained of is not authorized by the copyright owner, its agent, or the law."));
            }
            elseif ($confirm2 != 'yes') {
                NotificationHelper::setError(TranslateHelper::t("report_abuse_error_confirm_2", "Please confirm the information in the notification is accurate, and, under the pains and penalties of perjury, that you are authorized to act on behalf of the copyright owner."));
            }

            // check file url is active and exists
            if (NotificationHelper::isErrors() == false) {
                // break apart url
                $file = FileHelper::loadByFullUrl($fileUrl);
                if (!$file) {
                    NotificationHelper::setError(TranslateHelper::t("report_abuse_error_could_not_find_file", "Could not find a file with that url, please check and try again."));
                }
                else {
                    // make sure the file is active
                    if ($file->status !== 'active') {
                        NotificationHelper::setError(TranslateHelper::t("report_abuse_error_file_not_active", "The file url you've set is not active."));
                    }
                }
            }

            // check captcha
            if ((!NotificationHelper::isErrors()) && (SITE_CONFIG_CAPTCHA_REPORT_FILE_FORM == 'yes')) {
                $resp = CoreHelper::captchaCheck();
                if ($resp == false) {
                    NotificationHelper::setError(TranslateHelper::t("invalid_captcha", "Captcha confirmation text is invalid."));
                }
            }

            // add to database and send email to admin
            if (NotificationHelper::isErrors() == false) {
                // add to database
                $fileReport = FileReport::create();
                $fileReport->file_id = $file->id;
                $fileReport->report_date = CoreHelper::sqlDateTime();
                $fileReport->reported_by_name = $reportedByName;
                $fileReport->reported_by_email = $reportedByEmail;
                $fileReport->reported_by_address = $reportedByAddress;
                $fileReport->reported_by_telephone_number = $reportedByTelephoneNumber;
                $fileReport->digital_signature = $digitalSignature;
                $fileReport->report_status = 'pending';
                $fileReport->reported_by_ip = CoreHelper::getUsersIPAddress();
                $fileReport->other_information = $otherInformation;
                if ($fileReport->save()) {
                    // send email
                    $subject = TranslateHelper::t('report_file_email_subject', 'New abuse report on [[[SITE_NAME]]]', array(
                                'SITE_NAME' => SITE_CONFIG_SITE_NAME));

                    $replacements = array(
                        'FILE_DETAILS' => $fileUrl,
                        'SITE_NAME' => SITE_CONFIG_SITE_NAME,
                        'WEB_ROOT' => WEB_ROOT,
                        'USERS_IP' => CoreHelper::getUsersIPAddress()
                    );
                    $defaultContent = "There is a new abuse report on [[[SITE_NAME]]] with the following details:<br/><br/>";
                    $defaultContent .= "***************************************<br/>";
                    $defaultContent .= "[[[FILE_DETAILS]]]<br/>";
                    $defaultContent .= "***************************************<br/>";
                    $defaultContent .= "Submitted IP: [[[USERS_IP]]]<br/>";
                    $defaultContent .= "***************************************<br/><br/>";
                    $defaultContent .= "Please login via [[[WEB_ROOT]]]/admin/ to investigate further.";
                    $htmlMsg = TranslateHelper::t('report_file_email_content', $defaultContent, $replacements);

                    CoreHelper::sendHtmlEmail(SITE_CONFIG_REPORT_ABUSE_EMAIL, $subject, $htmlMsg, SITE_CONFIG_REPORT_ABUSE_EMAIL, strip_tags(str_replace("<br/>", "\n", $htmlMsg)), false, $reportedByName, true, $reportedByEmail);

                    return $this->redirect(WEB_ROOT . '/report_file?s=1');
                }

                // show error
                NotificationHelper::setError(TranslateHelper::t("report_abuse_error_failed_reporting", "Failed reporting file, please try again later"));
            }
        }
        else {
            // if url has been passed
            if ($request->query->has('file_url')) {
                $fileUrl = trim($request->query->get('file_url'));
            }

            // if user logged in
            if ($Auth->loggedIn()) {
                $reportedByEmail = $Auth->user->email;
            }
        }

        // success handling
        if ($request->query->has('s')) {
            NotificationHelper::setSuccess(TranslateHelper::t('report_file_success', 'Thanks for submitting the information needed to report a file on our site. We\'ll review the file as soon as possible and remove if required.'));
        }

        // load template
        return $this->render('report_file.html', array(
                    'fileUrl' => $fileUrl,
                    'otherInformation' => $otherInformation,
                    'reportedByName' => $reportedByName,
                    'reportedByEmail' => $reportedByEmail,
                    'reportedByAddress' => $reportedByAddress,
                    'reportedByTelephoneNumber' => $reportedByTelephoneNumber,
                    'digitalSignature' => $digitalSignature,
                    'confirm1' => $confirm1,
                    'confirm2' => $confirm2,
                    'confirmOptions' => array('no' => TranslateHelper::t('report_file_no', 'no'), 'yes' => TranslateHelper::t('report_file_yes', 'yes')),
        ));
    }

    public function linkChecker() {
        // load libraries for later
        $Auth = AuthHelper::getAuth();
        $db = Database::getDatabase();
        $request = $this->getRequest();

        // send report if submitted
        $checkedUrls = array();
        $totalFound = 0;
        $totalActive = 0;
        $totalDisabled = 0;

        // send report if submitted
        if ($request->request->has('submitme')) {
            $fileUrls = trim($request->request->get('fileUrls'));
            if (strlen($fileUrls) == 0) {
                NotificationHelper::setError(TranslateHelper::t("link_checker_error_please_enter_1_or_more_urls", "Please enter 1 or more file urls."));
            }
            else {
                // standardise
                $fileUrls = str_replace(array("\n\r", "\r\n", "\r", "\n\n"), "\n", $fileUrls);
                $fileUrls = str_replace(array("\n\r", "\r\n", "\r", "\n\n"), "\n", $fileUrls);
                $fileUrlsArr = explode("\n", $fileUrls);
            }

            // make sure we do no more than 200
            if (NotificationHelper::isErrors() == false) {
                if (COUNT($fileUrlsArr) > 200) {
                    NotificationHelper::setError(TranslateHelper::t("link_checker_error_only_200_allowed", "Please enter less than 200 urls to check at once."));
                }
            }

            // check the urls
            if (NotificationHelper::isErrors() == false) {
                // standardise
                foreach ($fileUrlsArr AS $fileUrl) {
                    // get short url
                    $checkedUrls[$fileUrl] = 'not found';
                    $fileUrlParts = parse_url($fileUrl);
                    if (isset($fileUrlParts['path'])) {
                        $path = $fileUrlParts['path'];
                        $pathParts = explode("/", $path);
                        $shortUrl = trim(end($pathParts));

                        // if this looks like a filename, try one back
                        if (strpos($shortUrl, '.') !== false) {
                            array_pop($pathParts);
                            $shortUrl = trim(end($pathParts));
                        }

                        if (strlen($shortUrl)) {
                            $file = File::loadOneByShortUrl($shortUrl);
                            if ($file) {
                                // active
                                if ($file->status == 'active') {
                                    $checkedUrls[$fileUrl] = 'active';
                                    $totalActive++;
                                }
                                else {
                                    $checkedUrls[$fileUrl] = 'disabled';
                                    $totalDisabled++;
                                }
                                $totalFound++;
                            }
                        }
                    }
                }

                if ($totalFound === 0) {
                    NotificationHelper::setError(TranslateHelper::t("link_checker_result_none_found", "Could not find the url(s) entered, please check and try again."));
                }
                else {
                    NotificationHelper::setSuccess(TranslateHelper::t("link_checker_result", "Found [[[TOTAL_FOUND]]] link(s), [[[TOTAL_ACTIVE]]] active and [[[TOTAL_DISABLED]]] disabled.", array('TOTAL_FOUND' => $totalFound, 'TOTAL_ACTIVE' => $totalActive, 'TOTAL_DISABLED' => $totalDisabled)));
                }
            }
        }

        // load template
        return $this->render('link_checker.html', array(
                    'fileUrls' => $fileUrls,
                    'checkedUrls' => $checkedUrls,
        ));
    }

    public function search() {
        // require login
        if (SITE_CONFIG_ENABLE_FILE_SEARCH == 'no') {
            // disable
            return $this->redirect(WEB_ROOT);
        }

        // load for later
        $request = $this->getRequest();

        // load template
        return $this->render('search.html', array(
                    'documentTypes' => $this->_getDocumentTypes(),
                    'filterByType' => $request->query->has('filterByType') ? $request->query->get('filterByType') : '',
                    'filterText' => $request->query->has('filterText') ? $request->query->get('filterText') : '',
        ));
    }

    public function ajaxSearch() {
        // require login
        if (SITE_CONFIG_ENABLE_FILE_SEARCH == 'no') {
            // disable
            return $this->redirect(WEB_ROOT);
        }

        // load libraries for later
        $Auth = AuthHelper::getAuth();
        $db = Database::getDatabase();
        $request = $this->getRequest();

        // setup initial params
        $s = (int) $request->query->get('iDisplayStart');
        $l = (int) $request->query->get('iDisplayLength');
        $documentTypes = $this->_getDocumentTypes();

        // create json response
        $jsonRs = array();

        // prepare clause
        $clause = 'WHERE ';
        $clause .= 'status = "active" ';
        $clause .= 'AND file.isPublic > 0 ';

        // only files in public folders
        $publicFolderClause = 'SELECT ff1.id FROM file_folder ff1 WHERE ff1.isPublic > 0 AND IF(ff1.parentId IS NULL, 1, (SELECT IF(ff2.isPublic = 0, 0, IF(ff2.parentId IS NULL, ff2.isPublic, (SELECT ff3.isPublic FROM file_folder ff3 WHERE ff3.id = ff2.parentId))) FROM file_folder ff2 WHERE ff2.id = ff1.parentId)) > 0';
        $clause .= 'AND (file.folderId IN (' . $publicFolderClause . ') OR (file.folderId IS NULL))';

        // only files in users which have opted to share them
        $clause .= 'AND (file.userId IN (SELECT id FROM users WHERE isPublic = 1) OR (file.userId IS NULL))';

        // filterText
        $filterText = '';
        if ($request->query->has('filterText')) {
            $filterText = $request->query->get('filterText');
            $filterText = str_replace(array('"', '%', '*'), '', $filterText);
        }
        if (strlen($filterText)) {
            $clause .= 'AND (file.shortUrl = ' . $db->quote($filterText) . ' OR file.originalFilename LIKE "%' . $db->escape($filterText) . '%") ';
        }

        // load stats for tabs
        $jsonRs['iTotalAll'] = $db->getValue('SELECT COUNT(id) AS total '
                . 'FROM file ' . $clause);
        foreach ($documentTypes AS $documentType => $documentExt) {
            $arrKey = 'iTotal' . ucfirst($documentType);
            $jsonRs[$arrKey] = $db->getValue('SELECT COUNT(id) AS total '
                    . 'FROM file ' . $clause . ' '
                    . 'AND extension IN ("' . implode('","', explode(',', $documentExt['ext'])) . '")');
        }

        // filterType
        $filterType = '';
        if ($request->query->has('filterType')) {
            $filterType = $request->query->get('filterType');
        }

        // make filterType safe
        $filterTypeArr = array();
        if (strlen($filterType)) {
            if (isset($documentTypes[$filterType])) {
                $filterTypeExp = explode(',', $documentTypes[$filterType]['ext']);
                foreach ($filterTypeExp AS $filterTypeExpItem) {
                    // limit length
                    $filterTypeExpItem = substr($filterTypeExpItem, 0, 10);
                    $filterTypeExpItem = strtolower($filterTypeExpItem);

                    // remove unwanted characters
                    $filterTypeExpItem = str_replace(array(',', ')', '(', '$', '"', '\'', '&', '-', '*', '%'), '', $filterTypeExpItem);
                    if (strlen($filterTypeExpItem)) {
                        $filterTypeArr[] = $filterTypeExpItem;
                    }
                }
            }
        }
        if (count($filterTypeArr)) {
            $clause .= 'AND extension IN ("' . implode('","', $filterTypeArr) . '") ';
        }

        // load all
        $totalResults = $db->getValue('SELECT COUNT(id) AS total '
                . 'FROM file ' . $clause);

        // load filtered
        $results = $db->getRows('SELECT * '
                . 'FROM file ' . $clause . ' '
                . 'ORDER BY uploadedDate DESC '
                . 'LIMIT ' . $s . ',' . $l);

        $data = array();
        if ($results) {
            foreach ($results AS $result) {
                $fileObj = File::hydrateSingleRecord($result);
                $previewImageUrlMedium = FileHelper::getIconPreviewImageUrl($result, false, 160, false, 68, 68, 'middle');

                $lrs = array();

                $cell1 = '';
                $cell1 .= '<div class="start-icon"><a href="' . ValidationHelper::safeOutputToScreen($fileObj->getFullShortUrl()) . '" target="_blank"><img src="' . $previewImageUrlMedium . '" alt="' . ValidationHelper::safeOutputToScreen($result['extension']) . '" title="' . ValidationHelper::safeOutputToScreen($result['extension']) . '"/></a></div>';

                $cell1 .= '<div class="main-text">';
                $cell1 .= '<h6><a href="' . ValidationHelper::safeOutputToScreen($fileObj->getFullShortUrl()) . '" target="_blank">' . ValidationHelper::safeOutputToScreen($result['originalFilename']) . '</a></h6>';
                $cell1 .= '<a class="resultUrl" href="' . ValidationHelper::safeOutputToScreen($fileObj->getFullShortUrl()) . '" target="_blank">' . $fileObj->getFullShortUrl() . '</a>';
                $cell1 .= '<p>' . TranslateHelper::t('search_date_uploaded', 'Dated Uploaded') . ': ' . ValidationHelper::safeOutputToScreen(CoreHelper::formatDate($result['uploadedDate'], SITE_CONFIG_DATE_FORMAT)) . '&nbsp;&nbsp;' . TranslateHelper::t('search_filesize', 'Filesize') . ': ' . ValidationHelper::safeOutputToScreen(CoreHelper::formatSize($result['fileSize'])) . '<p>';
                $cell1 .= '</div>';

                $lrs[] = $cell1;
                $lrs[] = '<a href="' . ValidationHelper::safeOutputToScreen($fileObj->getFullShortUrl()) . '" target="_blank" class="btn btn--sm type--uppercase btn--primary-1"><span class="btn__text"><span>' . TranslateHelper::t('download', 'download') . '&nbsp;&nbsp;</span><i class="fa fa-download"></i></span></a>';

                $data[] = $lrs;
            }
        }

        $jsonRs['sEcho'] = intval($_GET['sEcho']);
        $jsonRs['iTotalRecords'] = $totalResults;
        $jsonRs['iTotalDisplayRecords'] = $totalResults;
        $jsonRs['aaData'] = $data;

        // load template
        return $this->renderJson($jsonRs);
    }
    
    public function api() {
        // load template
        return $this->render('api.html', array(
                    'apiUrl' => ApiV2Helper::getApiUrl(),
                    'showAdminEndpoints' => false,
        ));
    }

    private function _getDocumentTypes() {
        $documentTypes = array();
        $documentTypes['images'] = array('ext' => 'jpg,jpeg,gif,png,bmp,psd,ai,wbmp,tiff,ico,mng,psp,raw', 'icon' => 'Photo');
        $documentTypes['documents'] = array('ext' => 'doc,docx,xls,xlsx,pdf,ppt,pptx,odf,dot,epub,odm,ott,omm,pages,rtf,txt,indo', 'icon' => 'Testimonal');
        $documentTypes['videos'] = array('ext' => 'mp4,mpeg,avi,3gp,cam,flv,ogg,wmv,mkv', 'icon' => 'Video');
        $documentTypes['audio'] = array('ext' => 'mp3,wav,flac,wma,aac,ra,rm', 'icon' => 'Speaker');
        $documentTypes['archives'] = array('ext' => 'zip,rar,gzip,tar,tar.gz,gz', 'icon' => 'Folder-Zip');

        return $documentTypes;
    }

}
