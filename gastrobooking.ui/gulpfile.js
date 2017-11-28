const gulp = require('gulp');
const concat = require('gulp-concat');
const livereload = require('gulp-livereload');
const autoprefixer = require('gulp-autoprefixer');
const path = require('path');

const watchPath = {
  css: ['src/assets/**/*.css'],
  js: ['src/app/**/*.js'],
  html: ['src/index.html', 'src/app/**/*.html'],
};

// livereload
gulp.task('watch', () => {
  gulp.src([].concat(watchPath.html, watchPath.css, watchPath.js))
      .pipe(livereload());
});

gulp.task('default', [], () => {
  livereload.listen();
  gulp.watch([].concat(watchPath.html, watchPath.css, watchPath.js), ['watch']);
});
