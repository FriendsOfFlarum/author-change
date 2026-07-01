import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';

export default [
  new Extend.Admin()
    .permission(
      () => ({
        icon: 'fas fa-user-edit',
        label: app.translator.trans('fof-author-change.admin.permissions.edit-user'),
        permission: 'fof-author-change.edit-user',
      }),
      'moderate'
    )
    .permission(
      () => ({
        icon: 'far fa-clock',
        label: app.translator.trans('fof-author-change.admin.permissions.edit-date'),
        permission: 'fof-author-change.edit-date',
      }),
      'moderate'
    ),
];
