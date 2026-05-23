==== WPCargo Shipment Container Addon ====

 ===========================================================
            Plugin Description
 ===========================================================

WPCargo Shipment Container add on allows you to group multiple shipments travelling in the same vicinity into one full truckload. If you only use half or a quarter of a space in one truckload, you will typically still have to pay for the entire space. Shipment Container will help your clients save money by sharing the space with other shippers thus only paying for the space that their freight takes up. It is also recommended for companies with multiple branches to help them group shipments by location.

==== CHANGELOG ====
=Version 6.0.2
- Remove issue on trip information if POD is not available

=Version 6.0.1
- Fixed issue on agents fields select

=Version 6.0.0
- PHP 8.3 Compatibility Updated
- Remove unused library for optimizations

=Version 6.0.0
- PHP 8.3 Compatibility Updated
- Remove unused library for optimizations

=Version 5.2.0
- Fixed the issue on report generation for other wpcargo user roles
- Added weight unit on CBM / KILO value on PDF and CSV report

=Version 5.1.9
- Optimized query on containers dashboard page

=Version 5.1.8
- Added checker for  $branch_managerID  before in_inarray


=Version 5.1.7
- Added actual weight value for CBM as default  if Balikbayan Addon do not exists.

=Version 5.1.6
- Fixed the issue on adding shipments when editing container from the backend(wp-admin).

=Version 5.1.5
- Added filter hook for "wpcc_can_access_containers" function

=Version 5.1.4
- Added Hook for  Remarks tags in email notification {remarks}


=Version 5.1.3
- fixed container history date and time data
- optimized update on assigned shipments

=Version 5.1.2
- disable auto update by default 
- load speed in wpadmin optimized

=Version 5.1.1
- fixed issue with balikbayan compatibility


=Version 5.1.0
- Added disable local updates 


=Version 5.0.9
- fixed balikbayan integration hook on export PDF and CSV


= Version 5.0.8
- Tested and fixed bug  in php 8.2

= Version 5.0.7
- Fixed bulk assign container

= Version 5.0.6
- Fixed CSV and PDF export
- Added settings for pre assigned shipments
- Removed duplicate queries

= Version 5.0.0
- added CSV Export for Containers Manifest
- Modified Manifest PDF template
- Fixed issue on the pre assigned shipments
- Added Balikbayan Management addon Integration on export Manifest


= Version  4.9.9
- Add Adnvance email compatibility 

= Version  4.9.8
- fixed issue undefined property: WPCargo::$agents
- added 'wpc_additional_section' hook for the settings template

= Version  4.9.7
- fixed issue undefined property: WPCargo::$agents
- added 'wpc_additional_section' hook for the settings template
