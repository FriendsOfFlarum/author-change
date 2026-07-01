/// <reference types="flarum/@types/translator-icu-rich" />
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Discussion from 'flarum/common/models/Discussion';
import Post from 'flarum/common/models/Post';
import User from 'flarum/common/models/User';
import SearchState from 'flarum/forum/states/SearchState';
interface UpdateAuthorModalAttrs extends IInternalModalAttrs {
    related: Discussion | Post;
}
export default class UpdateAuthorModal extends Modal<UpdateAuthorModalAttrs> {
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
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): any;
    saveModel(model: Discussion | Post): Promise<import("flarum/common/Store").ApiResponseSingle<Post>> | Promise<import("flarum/common/Store").ApiResponseSingle<Discussion>>;
    onsubmit(event: Event): void;
}
export {};
