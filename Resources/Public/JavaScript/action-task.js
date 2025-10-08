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

import Modal from '@typo3/backend/modal.js';
import DocumentService from '@typo3/core/document-service.js';

DocumentService.ready().then(() => {
  document.addEventListener('click', function (e) {
    const link = e.target.closest('.t3js-confirm-trigger');
    if (!link) {
      return;
    }
    e.preventDefault();

    const title = link.dataset.title;
    const content = link.dataset.message;
    Modal.confirm(title, content, top.TYPO3.Severity.warning, [
      {
        text: 'Cancel',
        trigger: function () {
          Modal.dismiss();
        }
      },
      {
        text: 'Confirm',
        active: true,
        trigger: function () {
          window.location.href = link.getAttribute('href');
          Modal.dismiss();
        }
      },
    ]);
  });
});
