import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import PostControls from 'flarum/forum/utils/PostControls';
import Button from 'flarum/common/components/Button';
import Discussion from 'flarum/common/models/Discussion';
import Post from 'flarum/common/models/Post';
import UpdateAuthorModal from './components/UpdateAuthorModal';

app.initializers.add('fof-author-change', () => {
  extend(DiscussionControls, 'moderationControls', function (items, discussion: Discussion) {
    if (!app.forum.attribute('fofAuthorChangeCanEditUser') && !app.forum.attribute('fofAuthorChangeCanEditDate')) {
      return;
    }

    items.add(
      'fof-author-change',
      Button.component(
        {
          icon: 'fas fa-user-edit',
          onclick() {
            app.modal.show(UpdateAuthorModal, {
              related: discussion,
            });
          },
        },
        app.translator.trans('fof-author-change.forum.controls.edit')
      )
    );
  });

  extend(PostControls, 'moderationControls', function (items, post: Post) {
    if (!app.forum.attribute('fofAuthorChangeCanEditUser') && !app.forum.attribute('fofAuthorChangeCanEditDate')) {
      return;
    }

    items.add(
      'fof-author-change',
      Button.component(
        {
          icon: 'fas fa-user-edit',
          onclick() {
            app.modal.show(UpdateAuthorModal, {
              related: post,
            });
          },
        },
        app.translator.trans('fof-author-change.forum.controls.edit')
      )
    );
  });
});
