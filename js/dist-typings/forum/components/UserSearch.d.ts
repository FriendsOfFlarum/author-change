import Search, { SearchAttrs } from 'flarum/forum/components/Search';
import ItemList from 'flarum/common/utils/ItemList';
import User from 'flarum/common/models/User';
import type Mithril from 'mithril';
interface UserSearchAttrs extends SearchAttrs {
    onsubmit: (user: User) => void;
}
export default class UserSearch extends Search<UserSearchAttrs> {
    sourceItems(): ItemList<any>;
    oncreate(vnode: Mithril.VnodeDOM<UserSearchAttrs, this>): void;
    onupdate(vnode: Mithril.VnodeDOM<UserSearchAttrs, this>): void;
    private setPlaceholder;
}
export {};
