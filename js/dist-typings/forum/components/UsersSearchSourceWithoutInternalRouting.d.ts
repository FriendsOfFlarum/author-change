import * as Mithril from 'mithril';
import UsersSearchSource from 'flarum/forum/components/UsersSearchSource';
export default class UsersSearchSourceWithoutInternalRouting extends UsersSearchSource {
    view(query: string): Array<Mithril.Vnode>;
}
