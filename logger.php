<?php
class Logger {
	private static $showLog = false;
	private static $showCriticalOnly = false;

	public static function log( $source, $stringToLog, $isCritical ) {
		if ( Logger::$showLog ) {
			if ( $isCritical == true || $isCritical == Logger::$showCriticalOnly ) {
				//      if(!is_array($stringToLog))
				//       echo htmlspecialchars($stringToLog).'<br />';
				//      else
				echo '<div style="border-bottom: 2px solid #999; margin-bottom: 20px; padding-bottom: 20px;">START <code>' . $source . '</code>:<pre>' . $stringToLog . '</pre>END <code>' . $source . '</code></div>';
			}
		}
	}

	public static function enable( $showCriticalOnly ) {
		Logger::$showCriticalOnly = $showCriticalOnly;
		Logger::$showLog = true;
	}

	public static function disable() {
		Logger::$showLog = false;
	}
}
?>
