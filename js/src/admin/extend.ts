import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';

export default [
  new Extend.Admin()
    .permission(
      () => ({
        icon: 'fas fa-user-edit',
        label: app.translator.trans('clarkwinkelmann-author-change.admin.permissions.edit-user'),
        permission: 'clarkwinkelmann-author-change.edit-user',
      }),
      'moderate'
    )
    .permission(
      () => ({
        icon: 'far fa-clock',
        label: app.translator.trans('clarkwinkelmann-author-change.admin.permissions.edit-date'),
        permission: 'clarkwinkelmann-author-change.edit-date',
      }),
      'moderate'
    ),
];
