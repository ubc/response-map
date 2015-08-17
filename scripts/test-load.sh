#!/bin/bash

wrk -c200 -d30s -t12 -s scripts/load.lua $1
