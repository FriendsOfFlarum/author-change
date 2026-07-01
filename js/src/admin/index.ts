import app from 'flarum/admin/app';

app.initializers.add('fof-author-change', () => {
  app.extensionData
    .for('fof-author-change')
    .registerPermission(
      {
        icon: 'fas fa-user-edit',
        label: app.translator.trans('fof-author-change.admin.permissions.edit-user'),
        permission: 'fof-author-change.edit-user',
      },
      'moderate'
    )
    .registerPermission(
      {
        icon: 'far fa-clock',
        label: app.translator.trans('fof-author-change.admin.permissions.edit-date'),
        permission: 'fof-author-change.edit-date',
      },
      'moderate'
    );
});
