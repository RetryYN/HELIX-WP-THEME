<?php
/** Isolated configuration boundary probe; never edits the WordPress installation. */
$root = dirname( __DIR__ );
$source = 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/inc/content-chrome.php';
if ( isset( $argv[1] ) && '--case' === $argv[1] ) {
 define( 'ABSPATH', __DIR__ );
 $fixture_path = $argv[2];
 $warnings = array();
 set_error_handler( function ( $level, $message ) use ( &$warnings ) { $warnings[] = $level; return true; } );
 function get_theme_file_path( $path ) { return $GLOBALS['fixture_path']; }
 function add_action() {}
 function add_filter() {}
 function is_singular( $type ) { return null === $type || '' === $type || 'wt_paid' === $type; }
 function is_post_type_archive( $type ) { return false; }
 function has_block( $block, $post ) { return is_string( $block ) && 'helix-wt/site-page' === $block; }
 function get_queried_object() { return null; }
 require $root . '/' . $source;
 $error = null; $face = null;
 try { $face = wtcf_chrome_face(); } catch ( Throwable $exception ) { $error = get_class( $exception ); }
 echo json_encode( array( 'warnings' => count( $warnings ), 'error' => $error, 'face' => $face ) );
 exit;
}
$cases = array(
 'missing' => array( null, null ),
 'invalid-json' => array( '{', null ),
 'null' => array( 'null', null ),
 'scalar' => array( '42', null ),
 'missing-post-type' => array( '{"bad":{}}', null ),
 'string-rule' => array( '{"bad":"wt_paid"}', null ),
 'empty-post-type' => array( '{"bad":{"post_type":""}}', null ),
 'array-post-type' => array( '{"bad":{"post_type":[]}}', null ),
 'invalid-block' => array( '{"bad":{"post_type":"wt_paid","block":[]}}', null ),
 'valid' => array( '{"content_paid":{"post_type":"wt_paid"}}', 'content_paid' ),
 'mixed' => array( '{"bad":{},"content_paid":{"post_type":"wt_paid"}}', 'content_paid' ),
);
$rows = array();
foreach ( $cases as $name => [ $content, $expected ] ) {
 $file = tempnam( sys_get_temp_dir(), 'wt-chrome-' );
 try {
  if ( null === $content ) { unlink( $file ); } else { file_put_contents( $file, $content ); }
  $cmd = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' --case ' . escapeshellarg( $file );
  $actual = json_decode( shell_exec( $cmd ), true );
  $pass = is_array( $actual ) && 0 === $actual['warnings'] && null === $actual['error'] && $expected === $actual['face'];
  $rows[] = array( 'name' => $name, 'pass' => $pass, 'actual' => $actual, 'expected' => $expected );
 } finally { if ( is_file( $file ) ) { unlink( $file ); } }
}
echo json_encode( array( 'completed' => true, 'sourceDigests' => array( $source => hash_file( 'sha256', $root . '/' . $source ), 'scripts/verify-chrome-config.php' => hash_file( 'sha256', __FILE__ ) ), 'rows' => $rows, 'limitation' => 'PHP isolated stubs for configuration boundaries; not a WordPress integration test.' ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";
exit( count( array_filter( $rows, fn( $row ) => ! $row['pass'] ) ) ? 1 : 0 );
