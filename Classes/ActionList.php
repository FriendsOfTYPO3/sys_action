<?php

declare(strict_types=1);

namespace TYPO3\CMS\SysAction;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;

/**
 * Class for the list rendering of Web>Task Center module
 * @internal
 */
class ActionList extends DatabaseRecordList
{
    /**
     * Creates the URL to this script, including all relevant GPvars
     * Fixed GPvars are id, table, imagemode, returnUrl, search_field, search_levels and showLimit
     * The GPvars "sortField" and "sortRev" are also included UNLESS they are found in the $excludeList variable.
     *
     * @param string $alternativeId Alternative id value. Enter blank string for the current id ($this->id)
     * @param string $table Table name to display. Enter "-1" for the current table.
     * @param string $excludeList Comma separated list of fields NOT to include ("sortField" or "sortRev")
     * @return string
     */
    public function listURL($alternativeId = '', $table = '-1', $excludeList = '')
    {
        $urlParameters = [];
        if ((string)$alternativeId !== '') {
            $urlParameters['id'] = $alternativeId;
        } else {
            $urlParameters['id'] = $this->id;
        }
        if ($table === '-1') {
            $urlParameters['table'] = $this->table;
        } else {
            $urlParameters['table'] = $table;
        }
        if ($this->returnUrl) {
            $urlParameters['returnUrl'] = $this->returnUrl;
        }
        if ((!$excludeList || !GeneralUtility::inList($excludeList, 'search_field')) && $this->searchString) {
            $urlParameters['search_field'] = $this->searchString;
        }
        if ($this->searchLevels) {
            $urlParameters['search_levels'] = $this->searchLevels;
        }
        if ($this->showLimit) {
            $urlParameters['showLimit'] = $this->showLimit;
        }
        if ((!$excludeList || !GeneralUtility::inList($excludeList, 'pointer')) && $this->page) {
            $urlParameters['pointer'] = $this->page;
        }
        if ((!$excludeList || !GeneralUtility::inList($excludeList, 'sortField')) && $this->sortField) {
            $urlParameters['sortField'] = $this->sortField;
        }
        if ((!$excludeList || !GeneralUtility::inList($excludeList, 'sortRev')) && $this->sortRev) {
            $urlParameters['sortRev'] = $this->sortRev;
        }
        if (GeneralUtility::_GP('SET')) {
            $urlParameters['SET'] = GeneralUtility::_GP('SET');
        }
        if (GeneralUtility::_GP('show')) {
            $urlParameters['show'] = (int)GeneralUtility::_GP('show');
        }
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute('user_task', $urlParameters);
    }
}
