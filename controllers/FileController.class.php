<?php

namespace Themes\Oydisk\Controllers;

use App\Controllers\FileController AS CoreFileController;
use App\Models\File;
use App\Helpers\CoreHelper;
use App\Helpers\NotificationHelper;
use App\Helpers\ThemeHelper;
use App\Helpers\TranslateHelper;

class FileController extends CoreFileController
{

    /**
     * File password page not needed in this theme.
     * 
     * @return type
     */
    public function filePassword() {
        // pickup request for later
        $request = $this->getRequest();

        // in this theme the permissions is handled by the folder
        $file = File::loadOneByShortUrl($request->query->get('file'));
        if (!$file) {
            // on failure
            return $this->redirect(ThemeHelper::getLoadedInstance()->getAccountWebRoot());
        }

        // redirect to folder, which then prompts for the password
        $folder = $file->getFolderData();
        if (!$folder) {
            // on failure
            return $this->redirect(ThemeHelper::getLoadedInstance()->getAccountWebRoot());
        }

        return $this->redirect($folder->getFolderUrl());
    }

    /**
     * Show the main file page with an additional param to show the file info tab
     * 
     * @return type
     */
    public function fileInfo($shortUrl) {
        // load file
        $file = File::loadOneByShortUrl($shortUrl);
        if (!$file) {
            // on failure
            return $this->redirect(WEB_ROOT . '/error?e=' . urlencode(TranslateHelper::t('delete_file_not_found', 'File not found.')));
        }

        // check file permissions, allow owners, non user uploads and admin/mods
        $Auth = $this->getAuth();
        if ($file->userId != null) {
            if ((($file->userId != $Auth->id) && ($Auth->level_id < 10))) {
                // if this is a private file
                if (CoreHelper::getOverallPublicStatus($file->userId, $file->folderId, $file->id) == false) {
                    return $this->redirect(WEB_ROOT . '/error?e=' . urlencode(TranslateHelper::t("error_file_is_not_publicly_shared", "File is not publicly available.")));
                }
            }
        }

        // load template
        return $this->render('file_info.html', array(
                    'file' => $file,
        ));
    }

    public function fileDelete($shortUrl) {
        // extract delete hash
        $deleteHash = '';
        $request = $this->getRequest();
        foreach ($request->query AS $k => $item) {
            if (strlen($k) === 32) {
                $deleteHash = $k;
            }
        }

        // make sure we have a delete hash
        if (strlen($deleteHash) !== 32) {
            return $this->redirect(WEB_ROOT . '/error?e=' . urlencode(TranslateHelper::t('delete_file_not_found', 'File not found.')));
        }

        // load file
        $file = File::loadOneByShortUrl($shortUrl);
        if (!$file) {
            return $this->redirect(WEB_ROOT . '/error?e=' . urlencode(TranslateHelper::t('delete_file_not_found', 'File not found.')));
        }

        // check delete hash against file
        if ($file->deleteHash != $deleteHash) {
            return $this->redirect(WEB_ROOT . '/error?e=' . urlencode(TranslateHelper::t('delete_file_not_found', 'File not found.')));
        }

        if ($request->request->has('submitted') && $file->status === 'active') {
			if (CoreHelper::inDemoMode() == true) {
				NotificationHelper::setError(TranslateHelper::t("no_changes_in_demo_mode"));
			}
			else {
				// reomve file
				$file->trashByUser();

				// setup success for template
				NotificationHelper::setSuccess(TranslateHelper::t('file_permanently_removed', 'File permanently removed.'));
			}
        }
        elseif ($file->status !== 'active') {
            // setup success for template
            NotificationHelper::setSuccess(TranslateHelper::t('file_permanently_removed', 'File permanently removed.'));
        }

        // load template
        return $this->render('file_delete.html', array(
                    'file' => $file,
        ));
    }

}
