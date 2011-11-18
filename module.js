/**
 * Javascript helper function for subpage
 *
 * @package    mod
 * @subpackage subpage
 * @copyright  2011 Dan Marsden  {@link http://danmarsden.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.mod_subpage = M.mod_subpage || {};

//drag drop based on example from :http://yuiblog.com/sandbox/yui/3.2.0pr1/examples/dd/list-drag.html
//Copyright 2010 Yahoo! Inc.
M.mod_subpage.init_dragdrop = function(Y,url2d,strmove, subpageid, sesskey) {

    //Listen for all drop:over events
    Y.DD.DDM.on('drop:over', function(e) {
        //Get a reference to our drag and drop nodes
        var drag = e.drag.get('node'),
            drop = e.drop.get('node');

        if (drag.hasClass('activity') && drop.hasClass('main')) {
            //This is an activity - don't allow it to be dragged between a section.
        } else if (drag.hasClass('main') && drop.hasClass('activity')){
            //this is a section - don't allow it to be dropped into a module area.
        } else if (drop.get('tagName').toLowerCase() === 'li') {  //Are we dropping on a li node?
            //Are we not going up?
            if (!goingUp) {
                drop = drop.get('nextSibling');
            }
            //Add the node to this list
            e.drop.get('node').get('parentNode').insertBefore(drag, drop);
            //Resize this nodes shim, so we can drop on it later.
            e.drop.sizeShim();
        }
    });
    //Listen for all drag:drag events
    Y.DD.DDM.on('drag:drag', function(e) {
        //Get the last y point
        var y = e.target.lastXY[1];
        //is it greater than the lastY2 var?
        if (y < lastY2) {
            //We are going up
            goingUp = true;
        } else {
            //We are going down.
            goingUp = false;
        }
        //Cache for next check
        lastY2 = y;
    });
    //Listen for all drag:start events
    Y.DD.DDM.on('drag:start', function(e) {
        //Get our drag object
        var drag = e.target;
        //Set some styles here
        drag.get('node').setStyle('opacity', '.25');
        drag.get('dragNode').set('innerHTML', drag.get('node').get('innerHTML'));
        drag.get('dragNode').setStyles({
            opacity: '.5',
            borderColor: drag.get('node').getStyle('borderColor'),
            backgroundColor: drag.get('node').getStyle('backgroundColor')
        });
    });
    //Listen for a drag:end events
    Y.DD.DDM.on('drag:end', function(e) {
         //Get a reference to our drag and drop nodes
        var drag = e.target.get('node');
        //Put our styles back
        drag.setStyles({
            visibility: '',
            opacity: '1'
        });
        //now do stuff.
        var sUrl = 'ajaxhandler.php';
        var dnext = drag.next();
        var dnextid = '';
        if (dnext !==null) {
            var dnextid = dnext.get('id');
        }
        if (drag.hasClass('activity')) {
            var sectionnode = drag.ancestor('li');
            var section = sectionnode.get('id');
            var cfg = {
                method: "POST",
                data: "action=moveactivity&subpage="+ subpageid +"&this="+drag.get('id')+"&next="+dnextid+"&section="+section+"&sesskey="+sesskey
            };
        } else {
            //this must be a section.
            var cfg = {
                method: "POST",
                data: "action=movesection&subpage="+ subpageid +"&this="+drag.get('id')+"&next="+dnextid+"&sesskey="+sesskey
            };
        }
        var request = Y.io(sUrl, cfg);

    });
    //Listen for all drag:drophit events
    Y.DD.DDM.on('drag:drophit', function(e) {
        var drop = e.drop.get('node'),
            drag = e.drag.get('node');

        //if we are not on an li, we must have been dropped on a ul
        if (drop.get('tagName').toLowerCase() !== 'li') {
            if (!drop.contains(drag)) {
                drop.appendChild(drag);
            }
        }
    });

    //Static Vars
    var goingUp = false, lastY2 = 0;

    //Get the list of li's in the lists and make them draggable
    var lis2 = Y.Node.all('.course-content .topics .content ul li');
    lis2.each(function(v, k) {
        var dd = new Y.DD.Drag({
            node: v,
            target: {
                padding: '0 0 0 20'
            }
        }).plug(Y.Plugin.DDProxy, {
            moveOnEnd: false
        }).plug(Y.Plugin.DDConstrained, {
            Node: '.course-content .topics .content'
        });
    });

     //Get the list of li's in the lists and make them draggable
    var lis = Y.Node.all('.topics .section');
    lis.each(function(v, k) {
        var dd = new Y.DD.Drag({
            node: v,
            target: {
                padding: '0 0 0 20'
            }
        }).plug(Y.Plugin.DDProxy, {
            moveOnEnd: false
        }).plug(Y.Plugin.DDConstrained, {
            Node: '.course-content .topics'
        });
    });

    //Create simple targets for the sections.
    var uls = Y.Node.all('.topics li');
    uls.each(function(v, k) {
        if ((v.hasClass('main') && v.hasClass('section')) || v.hasClass('activity')) {
            var tar = new Y.DD.Drop({
                node: v
            });
        }

    });

    //Add move section icon
    var moveicon = Y.Node.all('.left');
    moveicon.each(function (v, k) {
        var image = document.createElement('img');

        image.setAttribute('src', url2d);
        image.setAttribute('alt', strmove);
        image.setAttribute('style', 'cursor:move');
        image.setAttribute('class', 'iconsmall');
        v.setContent(image);
    });
    //hide html move section icons.
    var sectionmove = Y.Node.all('.section_move_commands');
    sectionmove.each(function (v, k) {
        v.remove();
    });
    //replace move module icon with ajax icon
    var moveicon2 = Y.Node.all('.editing_move');
    moveicon2.each(function (v, k) {
        var image = document.createElement('img');

        image.setAttribute('src', url2d);
        image.setAttribute('alt', strmove);
        image.setAttribute('style', 'cursor:move');
        image.setAttribute('class', 'iconsmall');
        v.replace(image);
    });
};