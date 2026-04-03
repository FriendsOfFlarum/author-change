import { IFormModalAttrs } from 'flarum/common/components/FormModal';
import FormModal from 'flarum/common/components/FormModal';
import Discussion from 'flarum/common/models/Discussion';
import Post from 'flarum/common/models/Post';
import User from 'flarum/common/models/User';
import SearchState from 'flarum/common/states/SearchState';
interface UpdateAuthorModalAttrs extends IFormModalAttrs {
    related: Discussion | Post;
}
export default class UpdateAuthorModal extends FormModal<UpdateAuthorModalAttrs> {
    user: User | null;
    createdAt?: string;
    editedAt: string;
    syncFirstPost: boolean;
    otherModelForFirstPostSync: Discussion | Post | null;
    attributes: any;
    dirty: boolean;
    loading: boolean;
    userSearchState: SearchState;
    oninit(vnode: any): void;
    showFirstPostSync(): boolean;
    isPost(): boolean;
    className(): string;
    title(): string | any[];
    content(): any;
    saveModel(model: Discussion | Post): Promise<import("flarum/common/Store").ApiResponseSingle<Post>> | Promise<import("flarum/common/Store").ApiResponseSingle<Discussion>>;
    onsubmit(event: Event): void;
}
export {};
