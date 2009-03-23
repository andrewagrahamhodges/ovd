/*
 *  SSHTools - Java SSH2 API
 *
 *  Copyright (C) 2002 Lee David Painter.
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU Library General Public License
 *  as published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 *
 *  You may also distribute it and/or modify it under the terms of the
 *  Apache style J2SSH Software License. A copy of which should have
 *  been provided with the distribution.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  License document supplied with your distribution for more details.
 *
 */

package com.sshtools.j2ssh.agent;

import java.awt.Component;
import java.io.IOException;
import java.util.Iterator;
import java.util.Map;
import java.util.Properties;
import java.util.Map.Entry;

import com.sshtools.j2ssh.SshClient;
import com.sshtools.j2ssh.authentication.AuthenticationProtocolClient;
import com.sshtools.j2ssh.authentication.AuthenticationProtocolException;
import com.sshtools.j2ssh.authentication.AuthenticationProtocolState;
import com.sshtools.j2ssh.authentication.SshAuthenticationClient;
import com.sshtools.j2ssh.authentication.SshMsgUserAuthPKOK;
import com.sshtools.j2ssh.authentication.SshMsgUserAuthRequest;
import com.sshtools.j2ssh.authentication.TerminatedStateException;
import com.sshtools.j2ssh.io.ByteArrayWriter;
import com.sshtools.j2ssh.transport.SshMessage;
import com.sshtools.j2ssh.transport.publickey.SshPublicKey;

/**
 * <p>
 * Provides an application with an authentication mechanism that links to the
 * sshtools agent; the agent stores private keys and can hash and sign data
 * for the public key authentication request.
 * </p>
 *
 * @author Lee David Painter
 * @version $Revision: 1.19 $
 */
public class AgentAuthenticationClient
    extends SshAuthenticationClient {

  /**  */
  protected SshAgentClient agent;

  /**
   * Creates a new AgentAuthenticationClient object.
   */
  public AgentAuthenticationClient() {
  }

  /*public void setKey(SshPublicKey key) {
      this.key = key;
       }*/
  public void setAgent(SshAgentClient agent) {
    this.agent = agent;
  }

  /**
   *
   */
  @Override
public void reset() {
    agent = null;
  }

  /**
   *
   *
   * @return
   */
  @Override
public String getMethodName() {
    return "publickey";
  }

  /**
   *
   *
   * @param authentication
   * @param username
   * @param serviceToStart
   * @param key
   *
   * @return
   *
   * @throws IOException
   */
  public boolean acceptsKey(AuthenticationProtocolClient authentication,
                            String username, String serviceToStart,
                            SshPublicKey key) throws IOException {
    authentication.registerMessage(SshMsgUserAuthPKOK.class,
                                   SshMsgUserAuthPKOK.SSH_MSG_USERAUTH_PK_OK);


    ByteArrayWriter baw = new ByteArrayWriter();

    // Now prepare and send the message
    baw.write(0);
    baw.writeString(key.getAlgorithmName());
    baw.writeBinaryString(key.getEncoded());

    SshMessage msg = new SshMsgUserAuthRequest(username, serviceToStart,
                                               getMethodName(), baw.toByteArray());

    authentication.sendMessage(msg);

    try {
      msg = authentication.readMessage(SshMsgUserAuthPKOK.
                                       SSH_MSG_USERAUTH_PK_OK);

      if (msg instanceof SshMsgUserAuthPKOK) {
        return true;
      }
      else {
        throw new IOException(
            "Unexpected message returned from readMessage");
      }
    }
    catch (TerminatedStateException ex) {
      return false;
    }
  }

  /**
   *
   *
   * @param authentication
   * @param serviceToStart
   *
   * @throws IOException
   * @throws TerminatedStateException
   * @throws AuthenticationProtocolException
   */
  @Override
public void authenticate(AuthenticationProtocolClient authentication,
                           String serviceToStart) throws IOException,
      TerminatedStateException {
    if ( (getUsername() == null) || (agent == null)) {
      throw new AuthenticationProtocolException(
          "You must supply a username and agent");
    }

    // Iterate the agents keys, find an acceptable key and authenticate
    Map<SshPublicKey, String> keys = agent.listKeys();
    Iterator<Entry<SshPublicKey, String>> it = keys.entrySet().iterator();
    boolean acceptable = false;
    SshPublicKey key = null;
    //String description;
    Map.Entry<SshPublicKey, String> entry;

    while (it.hasNext() && !acceptable) {
      entry = it.next();
      key = entry.getKey();
      //description = entry.getValue();
      acceptable = acceptsKey(authentication, getUsername(),
                              serviceToStart, key);
      /*log.info("Agent authentication with key " + key.getFingerprint()
               + " [" + description + "] is "
               + (acceptable ? " acceptable" : " not acceptable"));*/

      if (acceptable) {
        ByteArrayWriter baw = new ByteArrayWriter();

        // Now prepare and send the message
        baw.write(1);
        baw.writeString(key.getAlgorithmName());
        baw.writeBinaryString(key.getEncoded());

        // Create the signature data
        ByteArrayWriter data = new ByteArrayWriter();
        data.writeBinaryString(authentication.getSessionIdentifier());
        data.write(SshMsgUserAuthRequest.SSH_MSG_USERAUTH_REQUEST);
        data.writeString(getUsername());
        data.writeString(serviceToStart);
        data.writeString(getMethodName());
        data.write(1);
        data.writeString(key.getAlgorithmName());
        data.writeBinaryString(key.getEncoded());

        // Generate the signature
        baw.writeBinaryString(agent.hashAndSign(key, data.toByteArray()));

        SshMsgUserAuthRequest msg = new SshMsgUserAuthRequest(getUsername(),
            serviceToStart, getMethodName(), baw.toByteArray());

        authentication.sendMessage(msg);

        try {
          authentication.readAuthenticationState();
        }
        catch (TerminatedStateException ex) {
          if (ex.getState() == AuthenticationProtocolState.COMPLETE) {
            throw ex;
          }
        }
      }
    }

    throw new TerminatedStateException(AuthenticationProtocolState.FAILED);
  }

  /**
   *
   *
   * @param parent
   *
   * @return
   */
  public boolean showAuthenticationDialog(Component parent) {
    return false;
  }

  /**
   *
   *
   * @return
   */
  @Override
public Properties getPersistableProperties() {
    Properties properties = new Properties();

    return properties;
  }

  /**
   *
   *
   * @param properties
   */
  @Override
public void setPersistableProperties(Properties properties) {
  }

  /**
   *
   *
   * @return
   */
  @Override
public boolean canAuthenticate() {
    return ( (agent != null) && (getUsername() != null));
  }

  /**
   *
   *
   * @param ssh
   *
   * @return
   */
  public boolean hasAcceptableKey(SshClient ssh) {
    try {
      Map<SshPublicKey, String> keys = agent.listKeys();

      SshPublicKey key;

      for (Iterator<SshPublicKey> x = keys.keySet().iterator(); x.hasNext(); ) {
        key = x.next();

        if (ssh.acceptsKey(getUsername(), key)) {
          return true;
        }
      }
    }
    catch (IOException ex) {
    }

    return false;
  }
}
