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

package com.sshtools.j2ssh.sftp;

import com.sshtools.j2ssh.io.ByteArrayReader;
import com.sshtools.j2ssh.io.ByteArrayWriter;
import com.sshtools.j2ssh.io.UnsignedInteger32;
import com.sshtools.j2ssh.io.UnsignedInteger64;
import com.sshtools.j2ssh.subsystem.SubsystemMessage;

/**
 *
 *
 * @author $author$
 * @version $Revision: 1.17 $
 */
public class SshFxpWrite
    extends SubsystemMessage
    implements MessageRequestId {
  /**  */
  public static final int SSH_FXP_WRITE = 6;
  private UnsignedInteger32 id;
  private byte[] handle;
  private UnsignedInteger64 offset;
  private byte[] data;

  /**
   * Creates a new SshFxpWrite object.
   */
  public SshFxpWrite() {
    super(SSH_FXP_WRITE);
  }

  /**
   * Creates a new SshFxpWrite object.
   *
   * @param id
   * @param handle
   * @param offset
   * @param data
   * @param off
   * @param len
   */
  public SshFxpWrite(UnsignedInteger32 id, byte[] handle,
                     UnsignedInteger64 offset, byte[] data, int off, int len) {
    super(SSH_FXP_WRITE);
    this.id = id;
    this.handle = handle;
    this.offset = offset;
    this.data = new byte[len];
    System.arraycopy(data, off, this.data, 0, len);
  }

  /**
   *
   *
   * @return
   */
  public UnsignedInteger32 getId() {
    return id;
  }

  /**
   *
   *
   * @return
   */
  public byte[] getHandle() {
    return handle;
  }

  /**
   *
   *
   * @return
   */
  public UnsignedInteger64 getOffset() {
    return offset;
  }

  /**
   *
   *
   * @return
   */
  public byte[] getData() {
    return data;
  }

  /**
   *
   *
   * @param bar
   *
   * @throws java.io.IOException
   * @throws com.sshtools.j2ssh.transport.InvalidMessageException DOCUMENT
   *         ME!
   */
  @Override
public void constructMessage(ByteArrayReader bar) throws java.io.IOException,
      com.sshtools.j2ssh.transport.InvalidMessageException {
    id = bar.readUINT32();
    handle = bar.readBinaryString();
    offset = bar.readUINT64();
    data = bar.readBinaryString();
  }

  /**
   *
   *
   * @return
   */
  @Override
public String getMessageName() {
    return "SSH_FXP_WRITE";
  }

  /**
   *
   *
   * @param baw
   *
   * @throws java.io.IOException
   * @throws com.sshtools.j2ssh.transport.InvalidMessageException DOCUMENT
   *         ME!
   */
  @Override
public void constructByteArray(ByteArrayWriter baw) throws java.io.
      IOException,
      com.sshtools.j2ssh.transport.InvalidMessageException {
    baw.writeUINT32(id);
    baw.writeBinaryString(handle);
    baw.writeUINT64(offset);
    baw.writeBinaryString(data);
  }
}
