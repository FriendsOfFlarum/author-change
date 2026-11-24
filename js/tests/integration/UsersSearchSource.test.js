import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import flatten from 'flat';
import jsYaml from 'js-yaml';
import fs from 'fs';
import UsersSearchSourceWithoutInternalRouting from '../../src/forum/components/UsersSearchSourceWithoutInternalRouting';

beforeAll(() => {
  bootstrapForum();
  app.boot();
  app.translator.addTranslations(flatten(jsYaml.load(fs.readFileSync(new URL('../../../resources/locale/en.yml', import.meta.url), 'utf8'))));

  app.store.pushPayload({
    data: [
      { type: 'users', id: '2', attributes: { username: 'alice', displayName: 'alice' } },
      { type: 'users', id: '3', attributes: { username: 'zhang', displayName: '张伟' } },
    ],
  });
});

function render(query, minLength) {
  app.forum.pushAttributes({ searchMinLength: minLength });

  const root = document.createElement('ul');
  m.render(root, new UsersSearchSourceWithoutInternalRouting(() => {}).view(query));

  return root.textContent;
}

const typeMore = (count) => `Type ${count} characters to search the database`;
const anyHint = /Type \d+ characters?/;

test('no hint once the query reaches a configured minimum below 3', () => {
  expect(render('al', 2)).not.toMatch(anyHint);
  expect(render('al', 2)).toContain('alice');
});

test('the hint states the configured minimum', () => {
  expect(render('ali', 5)).toContain(typeMore(5));
});

test('a query shorter than the minimum shows the hint, not "No results"', () => {
  const text = render('alix', 5);

  expect(text).toContain(typeMore(5));
  expect(text).not.toContain('No results');
});

test('a query at the minimum with no match shows "No results"', () => {
  expect(render('xy', 2)).toContain('No results');
});

test('CJK characters each count once towards the minimum', () => {
  expect(render('张伟', 2)).not.toMatch(anyHint);
  expect(render('张伟', 2)).toContain('张伟');
});

test('characters outside the BMP count once, as in core', () => {
  // U+20000 is one CJK ideograph but two UTF-16 code units.
  expect(render('\u{20000}', 2)).toContain(typeMore(2));
});

test('without a configured minimum, the default of 3 applies', () => {
  expect(render('al', undefined)).toContain(typeMore(3));
  expect(render('ali', undefined)).not.toMatch(anyHint);
});

test('the length is counted on the lowercased query, as core gates the search', () => {
  // "İ" lowercases to "i" plus a combining dot: two code points.
  expect(render('\u0130', 2)).not.toMatch(anyHint);
});
