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
// `gulp clean-package` - Deletes the packaged theme.
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
    './*.json',
    './*.md',
    './lib/**/*',
    './images/**/*',
    './.idea/**/*'], {base:"."})
    .pipe(gulp.dest(packageDir));
});

// ### Package
// `gulp package` - Packages files into WordPress production ready theme.
gulp.task('package', function(callback){
  runSequence(
    'clean-package',
    'copy-plugin',
    'update-package-meta',
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
  return gulp.src(packageDir + '/**/*')
    .pipe(zip(pluginDirName + '.zip'))
    .pipe(gulp.dest('./package'));
});

// ### Gulp
// `gulp` - Run a complete build. To compile for production run `gulp --production`.
gulp.task('default', [], function() {
  gulp.start('package');
});