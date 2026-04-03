import * as Mithril from 'mithril';
import { SearchSource } from 'flarum/forum/components/Search';
import User from 'flarum/common/models/User';
export default class UsersSearchSourceWithoutInternalRouting implements SearchSource {
    protected results: Map<string, User[]>;
    protected onselect: (user: User) => void;
    constructor(onselect: (user: User) => void);
    search(query: string): Promise<void>;
    view(query: string): Array<Mithril.Vnode>;
}
