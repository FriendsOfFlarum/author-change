import * as Mithril from 'mithril';
import app from 'flarum/forum/app';
import { SearchSource } from 'flarum/forum/components/Search';
import UserSearchResult from 'flarum/common/components/UserSearchResult';
import User from 'flarum/common/models/User';
import Button from 'flarum/common/components/Button';

export default class UsersSearchSourceWithoutInternalRouting implements SearchSource {
  protected results = new Map<string, User[]>();
  protected onselect: (user: User) => void;

  constructor(onselect: (user: User) => void) {
    this.onselect = onselect;
  }

  async search(query: string): Promise<void> {
    return app.store
      .find<User[]>('users', {
        filter: { q: query },
        page: { limit: 5 },
      })
      .then((results) => {
        this.results.set(query, results);
        m.redraw();
      });
  }

  view(query: string): Array<Mithril.Vnode> {
    const lowerQuery = query.toLowerCase();

    const results = (this.results.get(lowerQuery) || [])
      .concat(
        app.store
          .all<User>('users')
          .filter((user) => [user.username(), user.displayName()].some((value) => value.toLowerCase().substring(0, lowerQuery.length) === lowerQuery))
      )
      .filter((e, i, arr) => arr.lastIndexOf(e) === i)
      .sort((a, b) => a.displayName().localeCompare(b.displayName()));

    if (results.length === 0) {
      if (query.length < 3) {
        return [m('li', Button.component({ icon: 'fas fa-info-circle' }, app.translator.trans('fof-author-change.forum.search.type-more')))];
      }

      return [m('li', Button.component({ icon: 'fas fa-search-minus' }, app.translator.trans('fof-author-change.forum.search.no-results')))];
    }

    const items = results.map((user) =>
      UserSearchResult.component({
        user,
        query,
        onclick: () => this.onselect(user),
      })
    );

    if (query.length < 3) {
      items.push(m('li', Button.component({ icon: 'fas fa-info-circle' }, app.translator.trans('fof-author-change.forum.search.type-more'))));
    }

    return items;
  }
}
