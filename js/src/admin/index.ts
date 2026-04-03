import app from 'flarum/admin/app';

export { default as extend } from './extend';

app.initializers.add('clarkwinkelmann-author-change', () => {
  // Nothing to do here.
  // We can either remove this initializer completely, or keep it so extensions can use `app.initializers.has('clarkwinkelmann-author-change')` to check if the extension is enabled, although `'clarkwinkelmann-author-change' in flarum.extensions` would work just as well.
});
