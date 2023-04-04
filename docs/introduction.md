---
title: Introduction
weight: 1
---

This package generates patch files in the same fashion Laravel generates migrations. Each file is timestamped with an up and a down method and is associated with a batch. You may run or rollback patches with the commands below.

This is a very simple package. It runs whatever is in your up and down methods on each patch in the order the patches are defined. It currently does not handle any errors or database transactions, please make sure you account for everything and have a backup plan when running patches in production.
