#!/bin/bash
while (true ) do
    xset s off
    xset -dpms
    matchbox-window-manager&
    firefox $FFURL
done;
