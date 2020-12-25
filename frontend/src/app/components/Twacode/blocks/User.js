import React, { Component } from 'react';
import UserService from 'services/user/user.js';
import Collections from 'services/Collections/Collections.js';
import ChannelsService from 'services/channels/channels.js';
import MenusManager from 'services/Menus/MenusManager.js';
import UserCard from 'app/components/UserCard/UserCard.js';
export default class User extends React.Component {
  constructor() {
    super();

    this.state = {
      userCard: false,
    };
  }

  componentWillMount() {
    if (this.props.id) {
      Collections.get('users').addListener(this);
    }
  }
  componentWillUnmount() {
    Collections.get('users').removeListener(this);
  }

  displayUserCard(user) {
    let box = window.getBoundingClientRect(this.user_node_details);
    this.setState({ userCard: !this.state.userCard });

    MenusManager.openMenu(
      [
        {
          type: 'react-element',
          reactElement: () => (
            <UserCard user={user} onClick={() => ChannelsService.openDiscussion([user.id])} />
          ),
        },
      ],
      box,
      null,
      { margin: 8 },
    );
  }

  render() {
    const highlighted =
      this.props.id == UserService.getCurrentUserId() ||
      this.props.username == 'here' ||
      this.props.username == 'all';
    if (!this.props.id) {
      return (
        <span className={'user_twacode ' + (highlighted ? 'highlighted' : '')}>
          @{this.props.username}
        </span>
      );
    }
    var id = this.props.id;
    var user = Collections.get('users').find(id);
    if (user) {
      return (
        <div
          ref={node => (this.user_node_details = node)}
          className={'user_twacode with_user ' + (highlighted ? 'highlighted' : '')}
          onClick={() => {
            this.displayUserCard(user);
          }}
        >
          <div
            className="userimage"
            style={{ backgroundImage: "url('" + UserService.getThumbnail(user) + "')" }}
          />
          {UserService.getFullName(user)}
        </div>
      );
    } else {
      UserService.asyncGet(id);
      return (
        <span className={'user_twacode ' + (highlighted ? 'highlighted' : '')}>
          @{this.props.username}
        </span>
      );
    }
  }
}
