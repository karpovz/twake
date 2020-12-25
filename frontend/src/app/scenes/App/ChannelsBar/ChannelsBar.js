import React, { Component } from 'react';
import PerfectScrollbar from 'react-perfect-scrollbar';
import Workspaces from 'services/workspaces/workspaces.js';
import Collections from 'services/Collections/Collections.js';
import UserService from 'services/user/user.js';
import ChannelsService from 'services/channels/channels.js';
import Groups from 'services/workspaces/groups.js';
import WorkspaceUserRights from 'services/workspaces/workspace_user_rights.js';
import GroupSwitch from 'components/Leftbar/GroupSwitch/GroupSwitch.js';

import Languages from 'services/languages/languages.js';
import CurrentUser from './CurrentUser/CurrentUser.js';
import ChannelsApps from './ChannelsApps/ChannelsApps.js';
import ChannelsWorkspace from './ChannelsWorkspace/ChannelsWorkspace.js';
import ChannelsUser from './ChannelsUser/ChannelsUser.js';
import Tutorial from './Tutorial.js';
import Footer from './Footer.js';

import './ChannelsBar.scss';

export default class ChannelsBar extends Component {
  constructor() {
    super();
    Workspaces.addListener(this);
    Groups.addListener(this);
    Collections.get('groups').addListener(this);
    Collections.get('workspaces').addListener(this);
    WorkspaceUserRights.addListener(this);
  }
  componentDidMount() {
    this.componentDidUpdate();
  }

  componentWillUnmount() {
    Workspaces.removeListener(this);
    Groups.removeListener(this);
    WorkspaceUserRights.removeListener(this);
    Collections.get('groups').removeListener(this);
    Collections.get('workspaces').removeListener(this);
    Collections.get('channels').removeSource('channels_' + this.old_workspace);
    this.old_workspace = undefined;
  }

  componentDidUpdate() {
    if (this.old_workspace != Workspaces.currentWorkspaceId && Workspaces.currentWorkspaceId) {
      if (this.old_workspace) {
        Collections.get('channels').removeSource('channels_' + this.old_workspace);
      }

      Collections.get('channels').addSource(
        {
          http_base_url: 'channels',
          http_options: {
            workspace_id: Workspaces.currentWorkspaceId,
          },
          websockets: [
            {
              uri: 'channels/workspace/' + Workspaces.currentWorkspaceId,
              options: { type: 'channels/workspace' },
            },
            {
              uri:
                'channels/workspace_private/' +
                Workspaces.currentWorkspaceId +
                '/' +
                UserService.getCurrentUserId(),
              options: { type: 'channels/workspace_private' },
            },
          ],
        },
        'channels_' + Workspaces.currentWorkspaceId,
      );

      ChannelsService.initSelection();

      this.old_workspace = Workspaces.currentWorkspaceId;
    }
  }

  render() {
    var group = Collections.get('groups').find(Groups.currentGroupId);
    var workspace = Collections.get('workspaces').find(Workspaces.currentWorkspaceId);
    var no_workspace =
      Object.keys(Workspaces.user_workspaces).length <= 1 &&
      Object.keys(Groups.user_groups).length <= 1;

    return (
      <div className="channels_view fade_in">
        <CurrentUser />

        <PerfectScrollbar component="div">
          <ChannelsApps />

          <ChannelsWorkspace />

          <ChannelsUser />
        </PerfectScrollbar>

        <Tutorial />

        <Footer />
      </div>
    );
  }
}
