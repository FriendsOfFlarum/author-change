import app from 'flarum/forum/app';
import { IFormModalAttrs } from 'flarum/common/components/FormModal';
import FormModal from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import Discussion from 'flarum/common/models/Discussion';
import Post from 'flarum/common/models/Post';
import User from 'flarum/common/models/User';
import Avatar from 'flarum/common/components/Avatar';
import username from 'flarum/common/helpers/username';
import SearchState from 'flarum/common/states/SearchState';
import Switch from 'flarum/common/components/Switch';
import UserSearch from './UserSearch';

interface UpdateAuthorModalAttrs extends IFormModalAttrs {
  related: Discussion | Post;
}

export default class UpdateAuthorModal extends FormModal<UpdateAuthorModalAttrs> {
  user!: User | null;
  createdAt?: string;
  editedAt!: string;
  syncFirstPost: boolean = false;
  otherModelForFirstPostSync: Discussion | Post | null = null;
  attributes: any = {}; // What we will send to the server. We only send what changed
  dirty: boolean = false;
  loading: boolean = false;
  userSearchState!: SearchState;

  oninit(vnode: any) {
    super.oninit(vnode);

    const { related } = this.attrs;

    const editedAt = related instanceof Post && related.editedAt();

    this.user = related.user() || null;
    this.createdAt = related.createdAt()?.toISOString().slice(0, 16);
    this.editedAt = editedAt ? editedAt.toISOString().slice(0, 16) : '';
    this.userSearchState = new SearchState();

    if (this.showFirstPostSync()) {
      if (related instanceof Discussion) {
        this.otherModelForFirstPostSync = related.firstPost() || null;

        // The firstPost relationship will not be loaded if the discussion is accessed directly
        // we will try to find the first post in the store
        if (this.otherModelForFirstPostSync === null) {
          this.otherModelForFirstPostSync =
            app.store.all<Post>('posts').find((post) => {
              return post.number() === 1 && post.discussion() === related;
            }) || null;
        }
      } else {
        this.otherModelForFirstPostSync = related.discussion();
      }

      if (
        this.otherModelForFirstPostSync &&
        (this.otherModelForFirstPostSync.user() || null) === this.user &&
        this.otherModelForFirstPostSync.createdAt()?.toISOString().slice(0, 16) === this.createdAt &&
        this.createdAt // To make test fail if both createdAt are undefined
      ) {
        this.syncFirstPost = true;
      }
    }
  }

  showFirstPostSync(): boolean {
    return this.attrs.related instanceof Discussion || this.attrs.related.number() === 1;
  }

  isPost(): boolean {
    return this.attrs.related instanceof Post;
  }

  className() {
    return 'Update-Author-Modal';
  }

  title() {
    return app.translator.trans('fof-author-change.forum.modal.title-' + (this.isPost() ? 'post' : 'discussion'));
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          {app.forum.attribute('fofAuthorChangeCanEditUser') && (
            <div className="Form-group">
              <label>{app.translator.trans('fof-author-change.forum.modal.user')}</label>
              <div className="SelectedUser">
                <Avatar user={this.user} />
                {username(this.user)}
                {this.user && (
                  <Button
                    icon="fas fa-times"
                    className="Button Button--icon Button--link RemoveUserButton"
                    onclick={() => {
                      this.user = null;
                      this.attributes.relationships = {
                        // Model.save() drops a null relationship; false is sent as `data: null`.
                        user: false,
                      };
                      this.dirty = true;
                    }}
                  />
                )}
              </div>
              <UserSearch
                state={this.userSearchState}
                onsubmit={(user: User) => {
                  this.user = user;
                  this.attributes.relationships = { user };
                  this.dirty = true;
                  m.redraw();
                }}
              />
            </div>
          )}
          {app.forum.attribute('fofAuthorChangeCanEditDate') && [
            <div className="Form-group">
              <label>{app.translator.trans('fof-author-change.forum.modal.created_at')}</label>
              <input
                className="FormControl"
                type="datetime-local"
                required
                value={this.createdAt}
                onchange={(event: InputEvent) => {
                  const { value } = event.target as HTMLInputElement;

                  this.createdAt = value;
                  this.attributes.createdAt = value;
                  this.dirty = true;
                }}
                disabled={this.loading}
              />
            </div>,
            this.isPost() && (
              <div className="Form-group">
                <label>{app.translator.trans('fof-author-change.forum.modal.edited_at')}</label>
                <input
                  className="FormControl"
                  type="datetime-local"
                  value={this.editedAt}
                  onchange={(event: InputEvent) => {
                    const { value } = event.target as HTMLInputElement;

                    this.editedAt = value;
                    this.attributes.editedAt = value;
                    this.dirty = true;
                  }}
                  disabled={this.loading}
                />
              </div>
            ),
          ]}
          {this.showFirstPostSync() && (
            <div className="Form-group">
              <Switch
                state={this.syncFirstPost}
                onchange={(value: boolean) => {
                  this.syncFirstPost = value;
                  this.dirty = true;

                  // We only put the values inside attributes when they change
                  // but when switching the sync checkbox to true we want to force a sync of both attributes that
                  // are on discussion+post
                  if (value) {
                    this.attributes.relationships = {
                      user: this.user || false,
                    };

                    this.attributes.createdAt = this.createdAt;
                  }
                }}
                // Disable checkbox if other model isn't available since we won't have the ID to save it
                disabled={this.loading || !this.otherModelForFirstPostSync}
              >
                {app.translator.trans('fof-author-change.forum.modal.sync-with-' + (this.isPost() ? 'discussion' : 'post'))}
              </Switch>
              {!this.otherModelForFirstPostSync && (
                <div className="helpText">{app.translator.trans('fof-author-change.forum.modal.sync-impossible')}</div>
              )}
            </div>
          )}
          <div className="Form-controls">
            <Button disabled={!this.dirty} loading={this.loading} type="submit" className="Button Button--primary">
              {app.translator.trans('fof-author-change.forum.modal.submit')}
            </Button>
            <Button className="Button" onclick={() => app.modal.close()}>
              {app.translator.trans('fof-author-change.forum.modal.cancel')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  saveModel(model: Discussion | Post) {
    return model.save({
      // Clone this.attributes so Model.save doesn't delete the relationships key
      ...this.attributes,
    });
  }

  onsubmit(event: Event) {
    event.preventDefault();

    this.loading = true;

    this.saveModel(this.attrs.related)
      .then((() => {
        if (this.syncFirstPost && this.otherModelForFirstPostSync) {
          return this.saveModel(this.otherModelForFirstPostSync);
        }

        return Promise.resolve();
      }) as () => Promise<any>)
      .then(() => {
        this.loading = false;
        this.dirty = false;

        m.redraw();

        app.modal.close();
      })
      .catch((err) => {
        this.loading = false;
        m.redraw();
        throw err;
      });
  }
}
