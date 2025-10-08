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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyAction;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyValidator;
use TYPO3\CMS\Core\PasswordPolicy\Validator\Dto\ContextData;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Taskcenter\Controller\TaskModuleController;
use TYPO3\CMS\Taskcenter\TaskInterface;

/**
 * This class provides a task for the taskcenter
 * @internal
 */
class ActionTask implements TaskInterface
{
    /**
     * @var TaskModuleController
     */
    protected $taskObject;

    /**
     * All hook objects get registered here for later use
     *
     * @var array
     */
    protected $hookObjects = [];

    /**
     * URL to task module
     *
     * @var string
     */
    protected $moduleUrl;

    /**
     * @var IconFactory
     */
    protected $iconFactory;
    private PageRenderer $pageRenderer;
    protected PasswordPolicyValidator $passwordPolicyValidator;

    /**
     * Constructor
     * @param TaskModuleController $taskObject
     */
    public function __construct(TaskModuleController $taskObject, PageRenderer $pageRenderer)
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->moduleUrl = (string)$uriBuilder->buildUriFromRoute('user_task');
        $this->taskObject = $taskObject;
        $this->getLanguageService()->includeLLFile('EXT:sys_action/Resources/Private/Language/locallang.xlf');
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sys_action']['tx_sysaction_task'] ?? [] as $className) {
            $this->hookObjects[] = GeneralUtility::makeInstance($className);
        }
        $this->pageRenderer = $pageRenderer;
        $passwordPolicy = $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordPolicy'] ?? 'default';
        $this->passwordPolicyValidator = GeneralUtility::makeInstance(
            PasswordPolicyValidator::class,
            PasswordPolicyAction::UPDATE_USER_PASSWORD,
            is_string($passwordPolicy) ? $passwordPolicy : ''
        );
    }

    /**
     * This method renders the task
     *
     * @return string The task as HTML
     */
    public function getTask()
    {
        $content = '';
        $show = (int)($GLOBALS['TYPO3_REQUEST']->getParsedBody()['show'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['show'] ?? null);
        foreach ($this->hookObjects as $hookObject) {
            if (method_exists($hookObject, 'getTask')) {
                $show = $hookObject->getTask($show, $this);
            }
        }

        // If no task selected, render the menu
        if ($show == 0) {
            $content .= $this->taskObject->description($this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:sys_action'), $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:description'));
            $content .= $this->renderActionList();
        } else {
            $record = BackendUtility::getRecord('sys_action', $show);
            // If the action is not found
            if (empty($record)) {
                $this->addMessage(
                    $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:action_error-not-found'),
                    $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:action_error'),
                    ContextualFeedbackSeverity::ERROR
                );
            } else {
                // Render the task
                $content .= $this->taskObject->description($record['title'], $record['description']);
                // Output depends on the type
                switch ($record['type']) {
                    case 1:
                        $content .= $this->viewNewBackendUser($record);
                        break;
                    case 3:
                        $content .= $this->viewRecordList($record);
                        break;
                    case 4:
                        $content .= $this->viewEditRecord($record);
                        break;
                    case 5:
                        $this->viewNewRecord($record);
                        break;
                    default:
                        $this->addMessage(
                            $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:action_noType'),
                            $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:action_error'),
                            ContextualFeedbackSeverity::ERROR
                        );
                        $content .= $this->renderFlashMessages();
                }
            }
        }
        return $content;
    }

    /**
     * General overview over the task in the taskcenter menu
     *
     * @return string Overview as HTML
     */
    public function getOverview()
    {
        $content = '<p>' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:description')) . '</p>';
        // Get the actions
        $actionList = $this->getActions();
        if (!empty($actionList)) {
            $items = '';
            // Render a single action menu item
            foreach ($actionList as $action) {
                $active = ($GLOBALS['TYPO3_REQUEST']->getParsedBody()['show'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['show'] ?? null) === $action['uid'] ? 'active' : '';
                $items .= '<a class="list-group-item ' . $active . '" href="' . $action['link'] . '" title="' . htmlspecialchars($action['description']) . '">' . htmlspecialchars($action['title']) . '</a>';
            }
            $content .= '<div class="list-group">' . $items . '</div>';
        }
        return $content;
    }

    /**
     * Get all actions of an user. Admins can see any action, all others only those
     * which are allowed in sys_action record itself.
     *
     * @return array Array holding every needed information of a sys_action
     */
    protected function getActions()
    {
        $backendUser = $this->getBackendUser();
        $actionList = [];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_action');
        $queryBuilder->select('sys_action.*')
            ->from('sys_action');

        if (!empty($GLOBALS['TCA']['sys_action']['ctrl']['sortby'])) {
            $queryBuilder->orderBy('sys_action.' . $GLOBALS['TCA']['sys_action']['ctrl']['sortby']);
        }

        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(RootLevelRestriction::class, ['sys_action']));

        // Editors can only see the actions which are assigned to a usergroup they belong to
        if (!$backendUser->isAdmin()) {
            if (empty($backendUser->userGroupsUID)) {
                $groupList = 0;
            } else {
                $groupList = implode(',', $backendUser->userGroupsUID);
            }

            $queryBuilder->getRestrictions()
                ->add(GeneralUtility::makeInstance(HiddenRestriction::class));

            $queryBuilder
                ->join(
                    'sys_action',
                    'sys_action_asgr_mm',
                    'sys_action_asgr_mm',
                    $queryBuilder->expr()->eq(
                        'sys_action_asgr_mm.uid_local',
                        $queryBuilder->quoteIdentifier('sys_action.uid')
                    )
                )
                ->join(
                    'sys_action_asgr_mm',
                    'be_groups',
                    'be_groups',
                    $queryBuilder->expr()->eq(
                        'sys_action_asgr_mm.uid_foreign',
                        $queryBuilder->quoteIdentifier('be_groups.uid')
                    )
                )
                ->where(
                    $queryBuilder->expr()->in(
                        'be_groups.uid',
                        $queryBuilder->createNamedParameter(
                            GeneralUtility::intExplode(',', $groupList, true),
                            ArrayParameterType::INTEGER
                        )
                    )
                )
                ->groupBy('sys_action.uid');
        }
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $queryResult = $queryBuilder->executeQuery();
        while ($actionRow = $queryResult->fetchAssociative()) {
            $editActionLink = '';

            // Admins are allowed to edit sys_action records
            if ($this->getBackendUser()->isAdmin()) {
                $uidEditArgument = 'edit[sys_action][' . (int)$actionRow['uid'] . ']';

                $link = (string)$uriBuilder->buildUriFromRoute(
                    'record_edit',
                    [
                        $uidEditArgument => 'edit',
                        'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                    ]
                );

                $title = $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:edit-sys_action');
                $icon = $this->iconFactory->getIcon('actions-open', IconSize::SMALL)->render();
                $editActionLink = '<a class="btn btn-default btn-sm" href="' . htmlspecialchars($link) . '" title="' . htmlspecialchars($title) . '">';
                $editActionLink .= $icon . ' ' . htmlspecialchars($title) . '</a>';
            }

            $actionList[] = [
                'uid' => 'actiontask' . $actionRow['uid'],
                'title' => $actionRow['title'],
                'description' => $actionRow['description'],
                'descriptionHtml' => (
                    $actionRow['description']
                        ? '<p>' . nl2br(htmlspecialchars($actionRow['description'])) . '</p>'
                        : ''
                ) . $editActionLink,
                'link' => $this->moduleUrl
                    . '&SET[function]=sys_action.'
                    . self::class
                    . '&show='
                    . (int)$actionRow['uid'],
            ];
        }

        return $actionList;
    }

    /**
     * Render the menu of sys_actions
     *
     * @return string List of sys_actions as HTML
     */
    protected function renderActionList()
    {
        $content = '';
        // Get the sys_action records
        $actionList = $this->getActions();
        // If any actions are found for the current users
        if (!empty($actionList)) {
            $content .= $this->taskObject->renderListMenu($actionList);
        } else {
            $this->addMessage(
                $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:action_not-found-description'),
                $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:action_not-found'),
                ContextualFeedbackSeverity::INFO
            );
        }
        // Admin users can create a new action
        if ($this->getBackendUser()->isAdmin()) {
            /** @var UriBuilder $uriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $link = (string)$uriBuilder->buildUriFromRoute(
                'record_edit',
                [
                    'edit[sys_action][0]' => 'new',
                    'returnUrl' => $this->moduleUrl,
                ]
            );

            $title = $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:new-sys_action');
            $content .= '<p>' .
                '<a class="btn btn-default" href="' . htmlspecialchars($link) . '" title="' . htmlspecialchars($title) . '">' .
                $this->iconFactory->getIcon('actions-add', IconSize::SMALL)->render() . ' ' . htmlspecialchars($title) .
                '</a></p>';
        }
        return $content;
    }

    /**
     * Action to create a new BE user
     *
     * @param array $record sys_action record
     * @return string form to create a new user
     */
    protected function viewNewBackendUser($record)
    {
        $this->pageRenderer->loadJavaScriptModule('@friendsoftypo3/sys-action/action-task.js');

        $content = '';
        $beRec = BackendUtility::getRecord('be_users', (int)$record['t1_copy_of_user']);
        // A record is need which is used as copy for the new user
        if (!is_array($beRec)) {
            $this->addMessage(
                $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:action_notReady'),
                $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:action_error'),
                ContextualFeedbackSeverity::ERROR
            );
            $content .= $this->renderFlashMessages();
            return $content;
        }
        $vars = $GLOBALS['TYPO3_REQUEST']->getParsedBody()['data'] ?? [];
        $key = 'NEW';
        if ((int)($vars['sent'] ?? 0) === 1) {
            $errors = [];
            // Basic error checks
            if (!empty($vars['email']) && !GeneralUtility::validEmail($vars['email'])) {
                $errors[] = $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:error-wrong-email');
            }
            if (empty($vars['username'])) {
                $errors[] = $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:error-username-empty');
            }
            if ($vars['key'] === 'NEW' && empty($vars['password'])) {
                $errors[] = $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:error-password-empty');
            }

            if ((int)($GLOBALS['TYPO3_REQUEST']->getParsedBody()['be_users_uid'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['be_users_uid'] ?? null) > 0) {
                $currentRecord = BackendUtility::getRecord('be_users', (int)($GLOBALS['TYPO3_REQUEST']->getParsedBody()['be_users_uid'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['be_users_uid'] ?? null));
                $contextData = new ContextData('BE', $currentRecord['password']);
            } else {
                $contextData = new ContextData('BE');
            }

            if (!empty($vars['password']) && !$this->passwordPolicyValidator->isValidPassword($vars['password'], $contextData)) {
                $errors[] = $this->getLanguageService()->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:passwordPolicyFailed');
            }
            if ($vars['key'] !== 'NEW' && !$this->isCreatedByUser($vars['key'], $record)) {
                $errors[] = $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:error-wrong-user');
            }
            foreach ($this->hookObjects as $hookObject) {
                if (method_exists($hookObject, 'viewNewBackendUser_Error')) {
                    $errors = $hookObject->viewNewBackendUser_Error($vars, $errors, $this);
                }
            }
            // Show errors if there are any
            if (!empty($errors)) {
                $this->addMessage(
                    implode(LF, $errors),
                    $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:action_error'),
                    ContextualFeedbackSeverity::ERROR
                );
            } else {
                // Save user
                $key = $this->saveNewBackendUser($record, $vars);
                // Success message
                $message = $vars['key'] === 'NEW'
                    ? $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:success-user-created')
                    : $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:success-user-updated');
                $this->addMessage(
                    $message,
                    $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:success')
                );
            }
            $content .= $this->renderFlashMessages();
        }
        // Load BE user to edit
        if ((int)($GLOBALS['TYPO3_REQUEST']->getParsedBody()['be_users_uid'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['be_users_uid'] ?? null) > 0) {
            $tmpUserId = (int)($GLOBALS['TYPO3_REQUEST']->getParsedBody()['be_users_uid'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['be_users_uid'] ?? null);
            // Check if the selected user is created by the current user
            $rawRecord = $this->isCreatedByUser($tmpUserId, $record);
            if ($rawRecord) {
                // Delete user
                if (($GLOBALS['TYPO3_REQUEST']->getParsedBody()['delete'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['delete'] ?? null) == 1) {
                    $this->deleteUser($tmpUserId, $record['uid']);
                }
                $key = $tmpUserId;
                $vars = $rawRecord;
            }
        }
        $content .= '<form action="" class="panel panel-default" method="post" enctype="multipart/form-data">
                        <fieldset class="form-section">
                            <h4 class="form-section-headline">' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:action_t1_legend_generalFields')) . '</h4>
                            <div class="form-group">
                                <label for="field_disable">' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.disable')) . '</label>
                                <input type="checkbox" id="field_disable" name="data[disable]" value="1" class="checkbox" ' . ((int)($vars['disable'] ?? 0) === 1 ? ' checked="checked" ' : '') . ' />
                            </div>
                            <div class="form-group">
                                <label for="field_realname">' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.name')) . '</label>
                                <input type="text" id="field_realname" class="form-control" name="data[realName]" value="' . htmlspecialchars($vars['realName'] ?? '') . '" />
                            </div>
                            <div class="form-group">
                                <label for="field_username">' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.username')) . '</label>
                                <input type="text" id="field_username" class="form-control" name="data[username]" value="' . htmlspecialchars($vars['username'] ?? '') . '" />
                            </div>
                            <div class="form-group">
                                <label for="field_password">' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.password')) . '</label>
                                <input type="password" id="field_password" class="form-control" name="data[password]" value="" />
                                <p class="mt-2 mb-1 text-body-secondary">' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_password_policy.xlf:passwordRequirements.description')) . '</p>
                                <ul class="mb-0"><li class="text-body-secondary">' . implode('</li><li class="text-body-secondary">', $this->passwordPolicyValidator->getRequirements()) . '</li></ul>
                            </div>
                            <div class="form-group">
                                <label for="field_email">' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.email')) . '</label>
                                <input type="text" id="field_email" class="form-control" name="data[email]" value="' . htmlspecialchars($vars['email'] ?? '') . '" />
                            </div>
                        </fieldset>
                        <fieldset class="form-section">
                            <h4 class="form-section-headline">' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:action_t1_legend_configuration')) . '</h4>
                            <div class="form-group">
                                <label for="field_usergroup">' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.usergroup')) . '</label>
                                <select id="field_usergroup" class="form-select" style="height:250px" name="data[usergroup][]" multiple="multiple">
                                    ' . $this->getUsergroups($record, $vars) . '
                                </select>
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="data[key]" value="' . $key . '" />
                                <input type="hidden" name="data[sent]" value="1" />
                                <input class="btn btn-default" type="submit" value="' . htmlspecialchars($this->getLanguageService()->sL($key === 'NEW' ? 'LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:action_Create' : 'LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:action_Update')) . '" />
                            </div>
                        </fieldset>
                    </form>';
        $content .= $this->getCreatedUsers($record, $key);
        return $content;
    }

    /**
     * Delete a BE user and redirect to the action by its id
     *
     * @param int $userId Id of the BE user
     * @param int $actionId Id of the action
     */
    protected function deleteUser($userId, $actionId)
    {
        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_users')->update(
            'be_users',
            ['deleted' => 1, 'tstamp' => (int)GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp')],
            ['uid' => (int)$userId]
        );

        $response = new RedirectResponse($this->moduleUrl . '&show=' . (int)$actionId);
        throw new PropagateResponseException($response, 1725019228);
    }

    /**
     * Check if a BE user is created by the current user
     *
     * @param int $id Id of the BE user
     * @param array $action sys_action record.
     * @return mixed The record of the BE user if found, otherwise FALSE
     */
    protected function isCreatedByUser($id, $action)
    {
        $record = BackendUtility::getRecord('be_users', $id, '*', ' AND cruser_id=' . $this->getBackendUser()->user['uid'] . ' AND createdByAction=' . $action['uid']);
        if (is_array($record)) {
            return $record;
        }
        return false;
    }

    /**
     * Render all users who are created by the current BE user including a link to edit the record
     *
     * @param array $action sys_action record.
     * @param int $selectedUser Id of a selected user
     * @return string html list of users
     */
    protected function getCreatedUsers($action, $selectedUser)
    {
        $content = '';
        $userList = [];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('be_users');

        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $res = $queryBuilder
            ->select('*')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->eq(
                    'cruser_id',
                    $queryBuilder->createNamedParameter($this->getBackendUser()->user['uid'], ParameterType::INTEGER)
                ),
                $queryBuilder->expr()->eq(
                    'createdByAction',
                    $queryBuilder->createNamedParameter($action['uid'], ParameterType::INTEGER)
                )
            )
            ->orderBy('username')
            ->executeQuery();

        // Render the user records
        while ($row = $res->fetchAssociative()) {
            $icon = '<span title="' . htmlspecialchars('uid=' . $row['uid']) . '">' . $this->iconFactory->getIconForRecord('be_users', $row, IconSize::SMALL)->render() . '</span>';
            $line = $icon . $this->action_linkUserName($row['username'], $row['realName'], $action['uid'], $row['uid']);
            // Selected user
            if ($row['uid'] == $selectedUser) {
                $line = '<strong>' . $line . '</strong>';
            }
            $userList[] = '<li class="list-group-item">' . $line . '</li>';
        }

        // If any records found
        if (!empty($userList)) {
            $content .= '<div class="panel panel-default">';
            $content .= '<div class="panel-heading">';
            $content .= '<h3 class="panel-title">' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:action_t1_listOfUsers')) . '</h3>';
            $content .= '</div>';
            $content .= '<ul class="list-group">' . implode($userList) . '</ul>';
            $content .= '</div>';
        }
        return $content;
    }

    /**
     * Create a link to edit a user
     *
     * @param string $username Username
     * @param string $realName Real name of the user
     * @param int $sysActionUid Id of the sys_action record
     * @param int $userId Id of the user
     * @return string html link
     */
    protected function action_linkUserName($username, $realName, $sysActionUid, $userId)
    {
        if (!empty($realName)) {
            $username .= ' (' . $realName . ')';
        }
        // Link to update the user record
        $href = $this->moduleUrl . '&SET[function]=sys_action.TYPO3\\CMS\\SysAction\\ActionTask&show=' . (int)$sysActionUid . '&be_users_uid=' . (int)$userId;
        $link = '<a href="' . htmlspecialchars($href) . '">' . htmlspecialchars($username) . '</a>';
        // Link to delete the user record
        $link .= '
				<a href="' . htmlspecialchars($href . '&delete=1') . '" class="t3js-confirm-trigger" data-title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:lDelete_warning_title')) . '" data-message="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:lDelete_warning')) . '">'
                    . $this->iconFactory->getIcon('actions-edit-delete', IconSize::SMALL)->render() .
                '</a>';
        return $link;
    }

    /**
     * Save/Update a BE user
     *
     * @param array $record Current action record
     * @param array $vars POST vars
     * @return int Id of the new/updated user
     */
    protected function saveNewBackendUser($record, $vars)
    {
        // Check if the usergroup is allowed
        $vars['usergroup'] = $this->fixUserGroup($vars['usergroup'] ?? [], $record);
        $key = $vars['key'];
        $vars['password'] = trim($vars['password']);
        $data = '';
        $newUserId = 0;
        if ($key === 'NEW') {
            $beRec = BackendUtility::getRecord('be_users', (int)$record['t1_copy_of_user']);
            if (is_array($beRec)) {
                $data = [];
                $data['be_users'][$key] = $beRec;
                $data['be_users'][$key]['username'] = $this->fixUsername($vars['username'], $record['t1_userprefix']);
                $data['be_users'][$key]['password'] = $vars['password'];
                $data['be_users'][$key]['realName'] = $vars['realName'];
                $data['be_users'][$key]['email'] = $vars['email'];
                $data['be_users'][$key]['disable'] = (int)($vars['disable'] ?? 0);
                $data['be_users'][$key]['admin'] = 0;
                $data['be_users'][$key]['usergroup'] = $vars['usergroup'] ?? '';
                $data['be_users'][$key]['createdByAction'] = $record['uid'];

                // UID column must be ignored
                unset($data['be_users'][$key]['uid']);
                // cruser_id must be ignored (will be set by datahander hook
                unset($data['be_users'][$key]['cruser_id']);
            }
        } else {
            // Check ownership
            $beRec = BackendUtility::getRecord('be_users', (int)$key);
            if (is_array($beRec) && $beRec['cruser_id'] == $this->getBackendUser()->user['uid']) {
                $data = [];
                $data['be_users'][$key]['username'] = $this->fixUsername($vars['username'], $record['t1_userprefix']);
                if ($vars['password'] !== '') {
                    $data['be_users'][$key]['password'] = $vars['password'];
                }
                $data['be_users'][$key]['realName'] = $vars['realName'];
                $data['be_users'][$key]['email'] = $vars['email'];
                $data['be_users'][$key]['disable'] = (int)($vars['disable'] ?? 0);
                $data['be_users'][$key]['admin'] = 0;
                $data['be_users'][$key]['usergroup'] = $vars['usergroup'];
                $newUserId = $key;
            }
        }
        // Save/update user by using DataHandler
        if (is_array($data)) {
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start($data, [], $this->getBackendUser());
            $dataHandler->admin = true;
            $dataHandler->process_datamap();
            $newUserId = (int)($dataHandler->substNEWwithIDs['NEW'] ?? 0);
            if ($newUserId) {
                // Create
                $this->action_createDir($newUserId);
            } else {
                // Update
                $newUserId = (int)$key;
            }
            unset($dataHandler);
        }
        return $newUserId;
    }

    /**
     * Create the username based on the given username and the prefix
     *
     * @param string $username Username
     * @param string $prefix Prefix
     * @return string Combined username
     */
    protected function fixUsername($username, $prefix)
    {
        $prefix = trim($prefix);
        if ($prefix !== '' && str_starts_with($username, $prefix)) {
            $username = substr($username, strlen($prefix));
        }
        return $prefix . $username;
    }

    /**
     * Clean the to be applied usergroups from not allowed ones
     *
     * @param array $appliedUsergroups Array of to be applied user groups
     * @param array $actionRecord The action record
     * @return array Cleaned array
     */
    protected function fixUserGroup($appliedUsergroups, $actionRecord)
    {
        if (is_array($appliedUsergroups)) {
            $cleanGroupList = [];
            // Create an array from the allowed usergroups using the uid as key
            $allowedUsergroups = array_flip(explode(',', $actionRecord['t1_allowed_groups']));
            // Walk through the array and check every uid if it is under the allowed ines
            foreach ($appliedUsergroups as $group) {
                if (isset($allowedUsergroups[$group])) {
                    $cleanGroupList[] = $group;
                }
            }
            $appliedUsergroups = $cleanGroupList;
        }
        return $appliedUsergroups;
    }

    /**
     * Create a user directory if defined
     *
     * @param int $uid Id of the user record
     */
    protected function action_createDir(int $uid): void
    {
        $path = $this->action_getUserMainDir();
        if ($path !== null) {
            GeneralUtility::mkdir($path . $uid);
            GeneralUtility::mkdir($path . $uid . '/_temp_/');
        }
    }

    /**
     * Get the path to the user home directory which is set in the localconf.php
     */
    protected function action_getUserMainDir(): ?string
    {
        $path = $GLOBALS['TYPO3_CONF_VARS']['BE']['userHomePath'] ?? null;
        // If path is set and a valid directory
        if ($path && @is_dir($path) && $GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath'] && \str_starts_with($path, $GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath']) && substr($path, -1) === '/') {
            return $path;
        }
        return null;
    }

    /**
     * Get all allowed usergroups which can be applied to a user record
     *
     * @param array $record sys_action record
     * @param array $vars Selected be_user record
     * @return string Rendered user groups
     */
    protected function getUsergroups($record, $vars)
    {
        $content = '';
        // Do nothing if no groups are allowed
        if (empty($record['t1_allowed_groups'])) {
            return $content;
        }
        $content .= '<option value=""></option>';
        $grList = GeneralUtility::trimExplode(',', $record['t1_allowed_groups'], true);
        foreach ($grList as $group) {
            $checkGroup = BackendUtility::getRecord('be_groups', $group);
            if (is_array($checkGroup)) {
                if (isset($vars['usergroup']) && is_array($vars['usergroup'])) {
                    $vars['usergroup'] = implode(',', $vars['usergroup']);
                }

                $selected = GeneralUtility::inList($vars['usergroup'] ?? '', $checkGroup['uid']) ? ' selected="selected" ' : '';
                $content .= '<option ' . $selected . 'value="' . (int)$checkGroup['uid'] . '">' . htmlspecialchars($checkGroup['title']) . '</option>';
            }
        }
        return $content;
    }

    /**
     * Action to create a new record
     *
     * @param array $record sys_action record
     */
    protected function viewNewRecord($record)
    {
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $link = (string)$uriBuilder->buildUriFromRoute(
            'record_edit',
            [
                'edit[' . $record['t3_tables'] . '][' . (int)$record['t3_listPid'] . ']' => 'new',
                'returnUrl' => $this->moduleUrl,
            ]
        );

        $response = new RedirectResponse($link);
        throw new PropagateResponseException($response, 9778567961);
    }

    /**
     * Action to edit records
     *
     * @param array $record sys_action record
     * @return string list of records
     */
    protected function viewEditRecord($record)
    {
        $content = '';
        $actionList = [];
        $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);
        $dbAnalysis->setFetchAllFields(true);
        $dbAnalysis->start($record['t4_recordsToEdit'], '*');
        $dbAnalysis->getFromDB();
        // collect the records
        foreach ($dbAnalysis->itemArray as $el) {
            $path = BackendUtility::getRecordPath(
                $el['id'],
                $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW),
                $this->getBackendUser()->uc['titleLen']
            );
            $record = BackendUtility::getRecord($el['table'], $dbAnalysis->results[$el['table']][$el['id']]);
            $title = BackendUtility::getRecordTitle($el['table'], $dbAnalysis->results[$el['table']][$el['id']]);
            $description = htmlspecialchars($this->getLanguageService()->sL($GLOBALS['TCA'][$el['table']]['ctrl']['title']));
            // @todo: which information could be needful
            if (isset($record['crdate'])) {
                $description .= ' - ' . htmlspecialchars(BackendUtility::dateTimeAge($record['crdate']));
            }
            /** @var UriBuilder $uriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $link = (string)$uriBuilder->buildUriFromRoute(
                'record_edit',
                [
                    'edit[' . $el['table'] . '][' . $el['id'] . ']' => 'edit',
                    'returnUrl' => $this->moduleUrl,
                ]
            );
            $actionList[$el['id']] = [
                'uid' => 'record-' . $el['table'] . '-' . $el['id'],
                'title' => $title,
                'description' => BackendUtility::getRecordTitle($el['table'], $dbAnalysis->results[$el['table']][$el['id']]),
                'descriptionHtml' => $description,
                'link' => $link,
                'icon' => '<span title="' . htmlspecialchars($path) . '">' . $this->iconFactory->getIconForRecord($el['table'], $dbAnalysis->results[$el['table']][$el['id']], IconSize::SMALL)->render() . '</span>',
            ];
        }
        // Render the record list
        $content .= $this->taskObject->renderListMenu($actionList);
        return $content;
    }

    /**
     * Action to create a list of records of a specific table and pid
     *
     * @param array $record sys_action record
     * @return string list of records
     */
    protected function viewRecordList($record)
    {
        $content = '';
        $id = (int)$record['t3_listPid'];
        $table = $record['t3_tables'];
        if ($id == 0) {
            $this->addMessage(
                $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:action_notReady'),
                $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:action_error'),
                ContextualFeedbackSeverity::ERROR
            );
            $content .= $this->renderFlashMessages();
            return $content;
        }
        // Loading current page record and checking access:
        $pageinfo = BackendUtility::readPageAccess(
            $id,
            $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)
        );
        $access = is_array($pageinfo);

        $pagePermissions = new Permission($this->getBackendUser()->calcPerms($pageinfo));
        $userCanEditPage = $pagePermissions->editPagePermissionIsGranted() && $id > 0 && ($this->getBackendUser()->isAdmin() || (int)$pageinfo['editlock'] === 0);
        $pageActionsInstruction = JavaScriptModuleInstruction::create('@typo3/backend/page-actions.js');
        if ($userCanEditPage) {
            $pageActionsInstruction->invoke('setPageId', $id);
        }
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction($pageActionsInstruction);

        // If there is access to the page, then render the list contents and set up the document template object:
        if ($access) {

            $this->pageRenderer->loadJavaScriptModule('@typo3/backend/recordlist.js');
            $this->pageRenderer->loadJavaScriptModule('@typo3/backend/record-download-button.js');
            $this->pageRenderer->loadJavaScriptModule('@typo3/backend/clear-cache.js');
            $this->pageRenderer->loadJavaScriptModule('@typo3/backend/record-search.js');
            $this->pageRenderer->loadJavaScriptModule('@typo3/backend/ajax-data-handler.js');
            $this->pageRenderer->loadJavaScriptModule('@typo3/backend/column-selector-button.js');
            $this->pageRenderer->loadJavaScriptModule('@typo3/backend/multi-record-selection.js');
            $this->pageRenderer->loadJavaScriptModule('@typo3/backend/clipboard-panel.js');
            $this->pageRenderer->loadJavaScriptModule('@typo3/backend/new-content-element-wizard-button.js');
            $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf');
            $this->pageRenderer->loadJavaScriptModule('@typo3/backend/element/immediate-action-element.js');
            $this->pageRenderer->loadJavaScriptModule('@typo3/backend/context-menu.js');

            // Initialize the dblist object:
            $dblist = GeneralUtility::makeInstance(ActionList::class);
            $dblist->setRequest($GLOBALS['TYPO3_REQUEST']);
            $dblist->calcPerms = $pagePermissions;
            $dblist->disableSingleTableView = true;
            $dblist->pageRow = $pageinfo;
            // Initialize the listing object, dblist, for rendering the list:
            $pointer = MathUtility::forceIntegerInRange($GLOBALS['TYPO3_REQUEST']->getParsedBody()['pointer'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['pointer'] ?? null, 0, 100000);
            $dblist->start($id, $table, $pointer);
            // Render the list of tables:
            $dblistContent = $dblist->generateList();

            // Begin to compile the whole page
            $content .= '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" name="dblistForm">' . $dblistContent . '<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" /></form>';
        } else {
            // Not enough rights to access the list view or the page
            $this->addMessage(
                $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:action_error-access'),
                $this->getLanguageService()->sL('LLL:EXT:sys_action/Resources/Private/Language/locallang.xlf:action_error'),
                ContextualFeedbackSeverity::ERROR
            );
            $content .= $this->renderFlashMessages();
        }
        return $content;
    }

    /**
     * @param string $message
     * @param string $title
     * @param ContextualFeedbackSeverity $severity
     *
     * @throws Exception
     */
    protected function addMessage($message, $title = '', $severity = ContextualFeedbackSeverity::OK)
    {
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $title, $severity);
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * Render all currently enqueued FlashMessages
     *
     * @return string
     */
    protected function renderFlashMessages()
    {
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        return $defaultFlashMessageQueue->renderFlashMessages();
    }

    /**
     * Returns LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
