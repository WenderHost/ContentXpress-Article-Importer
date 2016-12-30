// ## Globals
var git          = require('git-rev-sync');
var gulp         = require('gulp');
var json         = require('json-file');
var replace      = require('gulp-replace');
var rmdir        = require('rimraf');
var runSequence  = require('run-sequence');
var zip          = require('gulp-zip');

var pluginDirName = json.read('./package.json').get('pluginDirName');
var packageDir = './package/' + pluginDirName;

// ### Clean Package
// `gulp clean-package` - Deletes the packaged plugin.
gulp.task('clean-package', function(){
  rmdir('./package',function(error){});
});

// ### Copy Plugin
// `gulp copy-plugin` - Copies production files to package directory.
gulp.task('copy-plugin', function(){
  return gulp.src([
    './*.php',
    '!./controller.php',
    './*.css',
    './*.xsl',
    './*.js',
    '!./gulpfile.js',
    './*.json',
    '!./package.json',
    './*.md',
    './lib/**/*',
    './.idea/**/*'], {base:"."})
    .pipe(gulp.dest(packageDir));
});

// ### Package
// `gulp package` - Packages files into WordPress production ready plugin.
gulp.task('package', function(callback){
  runSequence(
    'clean-package',
    'copy-plugin',
    'zip-package',
    callback
  );
});

// ### Update Package Meta
// `gulp update-package-meta` - Updates the WordPress theme description with the latest commit information.
gulp.task('update-package-meta', function(){
  var latest_commit = git.short() + ' - ' + git.message();
  console.log('Latest commit: ' + latest_commit);
  var version_tag = git.tag();
  console.log('Version: ' + version_tag);
  return gulp.src(['./controller.php'])
    .pipe(replace('{latest_commit}', latest_commit))
    .pipe(replace('{version}', version_tag))
    .pipe(gulp.dest(packageDir));
});

// ### Zip Package
// `gulp zip-package` - Archive package files
gulp.task('zip-package',function(){
  return gulp.src( packageDir + '/**/*', { base: './package' } )
    .pipe( zip( pluginDirName + '.zip' ) )
    .pipe( gulp.dest( './package' ) );
});

// ### Gulp
// `gulp` - Run a complete build. To compile for production run `gulp --production`.
gulp.task('default', [], function() {
  gulp.start('package');
});

// `gulp clean` - Runs `clean-package`.
gulp.task('clean', [], function() {
  gulp.start('clean-package');
});