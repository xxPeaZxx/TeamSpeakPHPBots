/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */
/* 
 * Created on : 2nd July, 2016
 * Author     : Botorabi (boto)
 */

var LAYOUT = LAYOUT || {};


function initLAYOUT(logger) {
    
    LAYOUT.logger = logger;

    /**
     * Put the bot list into given table.
     * 
     * @param tableID           ID of table containig the bots
     * @param bots              Bot configuration array.
     * @param onClickHandler    Handler for onClick events
     */
    LAYOUT.putBotList = function(tableID, bots, onClickHandler) {
    
        var elem = document.getElementById(tableID);
        if (!elem)
            return;

        // are there any eventy at all?
        if (!bots || !bots.length)
            return;

        renderBots(elem, bots, onClickHandler);
    };

    /**
     * Put the user list into given table.
     * 
     * @param tableID           ID of table containig the bots
     * @param users             User data array.
     * @param onClickHandler    Handler for onClick events
     */
    LAYOUT.putUserList = function(tableID, users, onClickHandler) {
    
        var elem = document.getElementById(tableID);
        if (!elem)
            return;

        // are there any eventy at all?
        if (!users || !users.length)
            return;

        renderUsers(elem, users, onClickHandler);
    };

    /**
     * Animate a given element by rotating it.
     * 
     * @param elemId       Element ID
     * @param timeInterval Time interval for animation
     * @param step         Angle step of every tick
     * @returns            Animation timer object
     */
    LAYOUT.animateRoation = function (elemId, timeInterval, step) {
	var elem = $("#" + elemId);
        var degree = 0;
        function anim() {
            setTimeout(function() {
                degree += step;
                elem.css({ WebkitTransform: 'rotate(' + degree + 'deg)'});
                elem.css({ '-moz-transform': 'rotate(' + degree + 'deg)'});
                anim();
            }, timeInterval);
        }
        anim();
    };

    //! Private functions
    function renderBots(table, bots, onClickHandler) {

        if (table.tBodies.length === 0)
            return;

        var tbody = table.tBodies[0];

        var tr = null;
        var td = null;

        if (bots.length === 0) {
            return;
        }

        for (var i in bots) {

            tr = document.createElement("tr");
            tbody.appendChild(tr);

            td = document.createElement("td");
            tr.appendChild(td);
            td.innerHTML = bots[i].name;

            td = document.createElement("td");
            tr.appendChild(td);
            td.innerHTML = bots[i].botType;

            td = document.createElement("td");
            tr.appendChild(td);
            td.innerHTML = bots[i].description;

            td = document.createElement("td");
            td.setAttribute("class", "active");
            tr.appendChild(td);
            chbox = document.createElement("input");
            chbox.id = "chboxactiveId";
            chbox.type = "checkbox";
            chbox.botid = bots[i].id;
            if (bots[i].active === "1") {
                chbox.setAttribute("checked", true);
            }
            chbox.setAttribute("title", "An-/Ausschalten");
            chbox.onclick = function() { if (onClickHandler) onClickHandler((this.checked === true)? 'enableBot' : 'disableBot', this.botid); };
            td.appendChild(chbox);

            td = document.createElement("td");
            tr.appendChild(td);
            td.setAttribute("class", "mods");
            img = document.createElement("img");
            img.botid = bots[i].id;
            img.setAttribute("class", "imgClickable");
            img.setAttribute("src", "src/web/images/bot_edit.png");
            img.setAttribute("title", "Ändern");
            img.onclick = function() { if (onClickHandler) onClickHandler('modify', this.botid); };
            td.appendChild(img);
            br = document.createElement("br");
            td.appendChild(br);
            img = document.createElement("img");
            img.botid = bots[i].id;
            img.setAttribute("class", "imgClickable");
            img.setAttribute("src", "src/web/images/bot_delete.png");
            img.setAttribute("title", "Entfernen");
            img.onclick = function() { if (onClickHandler) onClickHandler('delete', this.botid); };
            td.appendChild(img);
        }
    }

    function formatTimeDate(date) {
        var datestr = date.getDate() + "." + (date.getMonth() + 1) + "." + date.getFullYear();
        var minutes = date.getMinutes();
        minutes = minutes < 10 ? "0" + minutes : minutes;
        datestr += " - " + date.getHours() + ":" + minutes;
        return datestr;
    }

    function renderUsers(table, users, onClickHandler) {

        if (table.tBodies.length === 0)
            return;

        var tbody = table.tBodies[0];

        var tr = null;
        var td = null;

        if (users.length === 0) {
            return;
        }

        for (var i in users) {

            tr = document.createElement("tr");
            tbody.appendChild(tr);

            td = document.createElement("td");
            tr.appendChild(td);
            td.innerHTML = users[i].name;

            td = document.createElement("td");
            tr.appendChild(td);
            td.innerHTML = users[i].login;

            td = document.createElement("td");
            tr.appendChild(td);
            td.innerHTML = users[i].description;

            td = document.createElement("td");
            tr.appendChild(td);
            var date = "-";
            if (parseInt(users[i].lastLogin) !== 0) {
                date = formatTimeDate(new Date(users[i].lastLogin * 1000));
            }
            td.innerHTML = date;

            td = document.createElement("td");
            td.setAttribute("class", "status");
            tr.appendChild(td);
            label = document.createElement("label");
            label.setAttribute("for", "chboxactiveId");
            label.innerHTML = "aktiv: ";
            td.appendChild(label);
            chbox = document.createElement("input");
            chbox.type = "checkbox";
            chbox.userid = users[i].id;
            if (users[i].active === "1") {
                chbox.setAttribute("checked", true);
            }
            chbox.setAttribute("title", "An-/Ausschalten");
            chbox.onclick = function() { if (onClickHandler) onClickHandler((this.checked === true)? 'enableUser' : 'disableUser', this.userid); };
            if ((parseInt(users[i].ops) & 4) === 0) {
                chbox.disabled = true;
            }
            td.appendChild(chbox);

            br = document.createElement("br");
            td.appendChild(br);
            br = document.createElement("br");
            td.appendChild(br);

            label = document.createElement("label");
            label.setAttribute("for", "cmborolesId");
            label.innerHTML = "Rolle: ";
            td.appendChild(label);
            sel = document.createElement("select");
            sel.id = "cmborolesId";
            sel.userid = users[i].id;
            sel.onchange = function() {
                if (onClickHandler) {
                    if (this.value === "1") {
                        onClickHandler("setRoleAdmin", this.userid);
                    }
                    else if (this.value === "2") {
                        onClickHandler("setRoleBotMaster", this.userid);
                    }
                }
            };
            if ((parseInt(users[i].ops) & 8) === 0) {
                sel.disabled = true;
            }

            td.appendChild(sel);

            opt = document.createElement("option");
            sel.appendChild(opt);
            opt.setAttribute("value", "1");
            opt.innerHTML = "Admin";
            if (users[i].roles & 1) {
                opt.setAttribute("selected", true);
            }

            opt = document.createElement("option");
            sel.appendChild(opt);
            opt.setAttribute("value", "2");
            opt.innerHTML = "Bot Master";
            if (users[i].roles & 2) {
                opt.setAttribute("selected", true);
            }

            td = document.createElement("td");
            tr.appendChild(td);
            td.setAttribute("class", "mods");
            if ((parseInt(users[i].ops) & 2) === 2) {
                img = document.createElement("img");
                img.userid = users[i].id;
                img.setAttribute("class", "imgClickable");
                img.setAttribute("src", "src/web/images/user_edit.png");
                img.setAttribute("title", "Ändern");
                img.onclick = function() { if (onClickHandler) onClickHandler('modify', this.userid); };
                td.appendChild(img);
                br = document.createElement("br");
                td.appendChild(br);
            }
            if ((parseInt(users[i].ops) & 1) === 1) {
                img = document.createElement("img");
                img.userid = users[i].id;
                img.setAttribute("class", "imgClickable");
                img.setAttribute("src", "src/web/images/user_delete.png");
                img.setAttribute("title", "Entfernen");
                img.onclick = function() { if (onClickHandler) onClickHandler('delete', this.userid); };
                td.appendChild(img);
            }
        }
    }
}