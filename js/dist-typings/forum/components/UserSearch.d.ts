import * as Mithril from 'mithril';
import Search, { SearchAttrs } from 'flarum/forum/components/Search';
import ItemList from 'flarum/common/utils/ItemList';
import User from 'flarum/common/models/User';
interface UserSearchAttrs extends SearchAttrs {
    onsubmit: (user: User) => void;
}
export default class UserSearch extends Search<UserSearchAttrs> {
    selectUserElement(searchResultElement: HTMLElement): void;
    oncreate(vnode: Mithril.Vnode<UserSearchAttrs, this>): void;
    selectResult(): void;
    clear(): void;
    sourceItems(): ItemList<any>;
    view(): any;
}
export {};
