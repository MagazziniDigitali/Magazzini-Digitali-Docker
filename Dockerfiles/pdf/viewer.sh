#!/bin/bash
while (true ) do
    xset s off
    xset -dpms
    matchbox-window-manager&
    su lowpriv -c "chromium --no-sandbox --app=\"$FFURL\""
done;
