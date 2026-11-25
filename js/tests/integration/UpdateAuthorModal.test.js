import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import flatten from 'flat';
import jsYaml from 'js-yaml';
import fs from 'fs';
import UpdateAuthorModal from '../../src/forum/components/UpdateAuthorModal';

beforeAll(() => {
  bootstrapForum();
  app.boot();
  app.translator.addTranslations(flatten(jsYaml.load(fs.readFileSync(new URL('../../../resources/locale/en.yml', import.meta.url), 'utf8'))));
  app.forum.pushAttributes({ fofAuthorChangeCanEditUser: true, fofAuthorChangeCanEditDate: true, canSearchUsers: true });

  app.store.pushPayload({
    data: {
      type: 'discussions',
      id: '1',
      attributes: { title: 'Hello', createdAt: '2020-01-01T10:00:00+00:00' },
      relationships: { user: { data: { type: 'users', id: '2' } }, firstPost: { data: { type: 'posts', id: '1' } } },
    },
    included: [
      { type: 'users', id: '2', attributes: { username: 'alice', displayName: 'alice' } },
      { type: 'users', id: '3', attributes: { username: 'bob', displayName: 'bob' } },
      {
        type: 'posts',
        id: '1',
        attributes: { number: 1, contentType: 'comment', createdAt: '2020-01-01T10:00:00+00:00' },
        relationships: { user: { data: { type: 'users', id: '2' } }, discussion: { data: { type: 'discussions', id: '1' } } },
      },
      {
        type: 'posts',
        id: '2',
        attributes: { number: 2, contentType: 'comment', createdAt: '2020-01-02T10:00:00+00:00', editedAt: '2020-01-03T10:00:00+00:00' },
        relationships: { user: { data: { type: 'users', id: '2' } }, discussion: { data: { type: 'discussions', id: '1' } } },
      },
    ],
  });
});

function open(related) {
  related.save = jest.fn(() => Promise.resolve(related));

  const modal = new UpdateAuthorModal();
  modal.oninit({ attrs: { related, state: { close: jest.fn() } } });

  return modal;
}

function render(modal) {
  const root = document.createElement('div');
  m.render(root, modal.content());

  return root;
}

const post = (id) => app.store.getById('posts', id);
const discussion = () => app.store.getById('discussions', '1');

test('a reply shows author, created and edited time, and no sync switch', () => {
  const root = render(open(post('2')));

  expect(root.querySelector('.SelectedUser').textContent).toContain('alice');
  expect(root.querySelectorAll('input[type=datetime-local]')).toHaveLength(2);
  expect(root.querySelector('.Checkbox')).toBeNull();
  expect(root.querySelector('.Form-controls button[type=submit]').disabled).toBe(true);
});

test('a discussion shows the created time only and syncs with its first post by default', () => {
  const modal = open(discussion());
  const root = render(modal);

  expect(root.querySelectorAll('input[type=datetime-local]')).toHaveLength(1);
  expect(root.querySelector('.Checkbox').textContent).toContain('Sync with first post');
  expect(modal.syncFirstPost).toBe(true);
});

test('the date fields are hidden without the date permission', () => {
  app.forum.pushAttributes({ fofAuthorChangeCanEditDate: false });
  const root = render(open(post('2')));
  app.forum.pushAttributes({ fofAuthorChangeCanEditDate: true });

  expect(root.querySelectorAll('input[type=datetime-local]')).toHaveLength(0);
  expect(root.querySelector('.SelectedUser')).not.toBeNull();
});

test('the author field is hidden without the author permission', () => {
  app.forum.pushAttributes({ fofAuthorChangeCanEditUser: false });
  const root = render(open(post('2')));
  app.forum.pushAttributes({ fofAuthorChangeCanEditUser: true });

  expect(root.querySelector('.SelectedUser')).toBeNull();
  expect(root.querySelectorAll('input[type=datetime-local]')).toHaveLength(2);
});

test('removing the author sends an empty user relationship', () => {
  const modal = open(post('2'));
  render(modal).querySelector('.RemoveUserButton').click();

  expect(modal.user).toBeNull();
  expect(modal.dirty).toBe(true);
  expect(modal.attributes.relationships).toStrictEqual({ user: [] });
  expect(render(modal).querySelector('.RemoveUserButton')).toBeNull();
});

test('changing the dates sends them', () => {
  const modal = open(post('2'));
  const [created, edited] = render(modal).querySelectorAll('input[type=datetime-local]');

  created.value = '2021-05-06T07:08';
  created.dispatchEvent(new window.Event('change'));
  edited.value = '2021-05-07T07:08';
  edited.dispatchEvent(new window.Event('change'));

  expect(modal.attributes).toStrictEqual({ createdAt: '2021-05-06T07:08', editedAt: '2021-05-07T07:08' });
  expect(modal.dirty).toBe(true);
});

test('submitting a synced discussion saves it and its first post', async () => {
  const modal = open(discussion());
  post('1').save = jest.fn(() => Promise.resolve(post('1')));
  app.modal = { close: jest.fn() };

  modal.user = app.store.getById('users', '3');
  modal.attributes.relationships = { user: modal.user };
  modal.onsubmit({ preventDefault() {} });
  await new Promise((resolve) => setTimeout(resolve));

  expect(discussion().save).toHaveBeenCalledWith({ relationships: { user: modal.user } });
  expect(post('1').save).toHaveBeenCalledWith({ relationships: { user: modal.user } });
  expect(app.modal.close).toHaveBeenCalled();
});
