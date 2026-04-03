import app from 'flarum/forum/app';
import Search, { SearchAttrs } from 'flarum/forum/components/Search';
import ItemList from 'flarum/common/utils/ItemList';
import User from 'flarum/common/models/User';
import UsersSearchSourceWithoutInternalRouting from './UsersSearchSourceWithoutInternalRouting';
import extractText from 'flarum/common/utils/extractText';
import type Mithril from 'mithril';

interface UserSearchAttrs extends SearchAttrs {
  onsubmit: (user: User) => void;
}

export default class UserSearch extends Search<UserSearchAttrs> {
  sourceItems(): ItemList<any> {
    const items = new ItemList();

    if (app.forum.attribute('canSearchUsers')) {
      items.add(
        'users',
        new UsersSearchSourceWithoutInternalRouting((user) => {
          this.attrs.onsubmit(user);
          this.clear();
        })
      );
    }

    return items;
  }

  oncreate(vnode: Mithril.VnodeDOM<UserSearchAttrs, this>) {
    super.oncreate(vnode);
    this.setPlaceholder();
  }

  onupdate(vnode: Mithril.VnodeDOM<UserSearchAttrs, this>) {
    super.onupdate(vnode);
    this.setPlaceholder();
  }

  private setPlaceholder() {
    const input = this.element.querySelector<HTMLInputElement>('input');
    if (input) {
      input.placeholder = extractText(app.translator.trans('clarkwinkelmann-author-change.forum.search.placeholder'));
      input.setAttribute('aria-label', input.placeholder);
    }
  }
}
