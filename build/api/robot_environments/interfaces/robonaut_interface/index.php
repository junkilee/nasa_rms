<?php
/**
 * A task scheduler interface for August Hackthon
 *
 * @author     Jun Ki Lee <jun_ki_lee@brown.edu>
 * @copyright  2013 Brown University
 * @license    BSD -- see LICENSE file
 * @version    June, 3 2013
 * @link       http://ros.org/wiki/rms_interactive_world
 */

/**
 * A static class to contain the interface generate function.
 *
 * @author     Jun Ki Lee <jun_ki_lee@brown.edu>
 * @copyright  2013 Brown University
 * @license    BSD -- see LICENSE file
 * @version    June, 3 2013
 */
class robonaut_interface
{
    /**
     * Generate the HTML for the interface. All HTML is echoed.
     * @param robot_environment $re The associated robot_environment object for
     *     this interface
     */
    function generate($re)
    {
        global $title;
        
        // check if we have enough valid widgets
        if (!$streams = $re->get_widgets_by_name('MJPEG Stream')) {
            robot_environments::create_error_page(
                'No MJPEG streams found.',
                $re->get_user_account()
            );
        } else if (!$teleop = $re->get_widgets_by_name('Keyboard Teleop')) {
            robot_environments::create_error_page(
                'No Keyboard Teloperation settings found.',
                $re->get_user_account()
            );
        } /*else if (!$im = $re->get_widgets_by_name('Interactive Markers')) {
            robot_environments::create_error_page(
                'No Interactive Marker settings found.',
                $re->get_user_account()
            );
        } else if (!$nav = $re->get_widgets_by_name('2D Navigation')) {
            robot_environments::create_error_page(
                'No 2D Navaigation settings found.',
                $re->get_user_account()
            );
        } */else if (!$re->authorized()) {
            robot_environments::create_error_page(
                'Invalid experiment for the current user.',
                $re->get_user_account()
            );
        } else {
            // lets create a string array of MJPEG streams
            $topics = '[';
            $labels = '[';
            foreach ($streams as $s) {
                $topics .= "'".$s['topic']."', ";
                $labels .= "'".$s['label']."', ";
            }
            $topics = substr($topics, 0, strlen($topics) - 2).']';
            $labels = substr($labels, 0, strlen($topics) - 2).']';

            // we will also need the map
            /*
            $widget = widgets::get_widget_by_table('maps');
            $map = widgets::get_widget_instance_by_widgetid_and_id(
                $widget['widgetid'], $nav[0]['mapid']
            );*/

            $collada = 'ColladaAnimationCompress/0.0.1/ColladaLoader2.min.js'?>
<!DOCTYPE html>
<html>
<head>
<?php $re->create_head() // grab the header information ?>
<title><?php echo $title?></title>
<script type="text/javascript"
  src="http://cdn.robotwebtools.org/threejs/r56/three.min.js">
</script>
<script type="text/javascript"
  src="http://cdn.robotwebtools.org/EventEmitter2/0.4.11/eventemitter2.js">
</script>
<script type="text/javascript"
  src="http://cdn.robotwebtools.org/<?php echo $collada?>">
</script>
<!-- http://cdn.robotwebtools.org/roslibjs/r5/ -->
<script type="text/javascript"
  src="/api/robot_environments/interfaces/robonaut_interface/roslib.js"></script>
<script type="text/javascript"
  src="http://cdn.robotwebtools.org/mjpegcanvasjs/r1/mjpegcanvas.min.js">
</script>
<script type="text/javascript"
  src="http://cdn.robotwebtools.org/keyboardteleopjs/r1/keyboardteleop.min.js">
</script>
<script type="text/javascript"
  src="http://cdn.robotwebtools.org/ros3djs/r6/ros3d.js">
</script>
<script type="text/javascript"
  src="http://cdn.robotwebtools.org/EaselJS/0.6.0/easeljs.min.js">
</script>
<script type="text/javascript"
  src="http://cdn.robotwebtools.org/ros2djs/r2/ros2d.min.js">
</script>
<script type="text/javascript"
  src="http://cdn.robotwebtools.org/nav2djs/r1/nav2d.min.js">
</script>
<script type="text/javascript">
  //connect to ROS
  var ros = new ROSLIB.Ros({
    url : '<?php echo $re->rosbridge_url()?>'
  });

  ros.on('error', function() {
	writeToTerminal('Connection failed!');
  });

  /**
   * Write the given text to the terminal.
   *
   * @param text - the text to add
   */
  function writeToTerminal(text) {
    var div = $('#terminal');
    div.append('<strong> &gt; '+ text + '</strong><br />');
    div.animate({
      scrollTop : div.prop("scrollHeight") - div.height()
    }, 50);
  }

  /**
   * Load everything on start.
   */
  function start() {
    // create MJPEG streams
    /*
    new MJPEGCANVAS.MultiStreamViewer({
      divID : 'video',
      host : '<?php echo $re->get_mjpeg()?>',
      port : '<?php echo $re->get_mjpegport()?>',
      width : 400,
      height : 300,
      topics : <?php echo $topics?>,
      labels : <?php echo $labels?>
    });*/

    // create the main viewer
    var viewer = new ROS3D.Viewer({
      divID : 'scene',
      width :  $(document).width(),
      height : $(document).height(),
      antialias : true
    });
    viewer.addObject(new ROS3D.Grid());

    // setup a client to listen to TFs
    var tfClient = new ROSLIB.TFClient({
      ros : ros,
      angularThres : 0.01,
      transThres : 0.01,
      rate : 10.0,
      fixedFrame : '/world'
    });
    
    /*
    new ROS3D.OccupancyGridClient({
      ros : ros,
      rootObject : viewer.scene,
      topic : '<?php echo $map['topic']?>',
      tfClient : tfClient
    });*/

    // setup the URDF client
    new ROS3D.UrdfClient({
      ros : ros,
      tfClient : tfClient,
      path : 'http://resources.robotwebtools.org/',
      rootObject : viewer.scene
    });

    // setup the marker clients
    // TODO : recopy this part from task_scheduler code

    // move the overlays
    $('#terminal').css({top:($(document).height()-$('#terminal').height())+'px'});

    // fixes the menu in the floating camera feed
    $('body').bind('DOMSubtreeModified', function() {
    	$('body div:last-child').css('z-index', 750);
    });

    writeToTerminal('Interface initialization complete.');
  }
</script>
</head>
<body onload="start();">
  <!-- <div class="mjpeg-widget" id="video"></div>-->
  <!-- <div class="nav-widget" id="nav"></div> -->
  <div id="terminal" class="terminal"></div>
  <div id="scene" class="scene"></div>
</body>
</html>
<?php
        }
    }
}
