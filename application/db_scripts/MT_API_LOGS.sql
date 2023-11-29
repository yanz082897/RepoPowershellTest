USE [GATEWAY_DB]
GO
/****** Object:  Table [dbo].[MT_API_LOGS]    Script Date: 06/02/2023 11:37:50 am ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[MT_API_LOGS](
	[MT_ID] [int] IDENTITY(1,1) NOT NULL,
	[IP_ADDRESS] [nvarchar](50) NULL,
	[USER_AGENT] [nvarchar](50) NULL,
	[RESPONSE] [nvarchar](max) NULL,
	[REQUEST] [nvarchar](max) NULL,
	[SESSION_ID] [nvarchar](50) NULL,
	[TIMESTAMP] [datetime] NULL,
	[MODULE] [nvarchar](50) NULL,
	[CONNECTION_ID] [nvarchar](50) NULL,
	[STATUS] [nvarchar](50) NULL,
	[MESSAGE] [nvarchar](max) NULL
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
ALTER TABLE [dbo].[MT_API_LOGS] ADD  CONSTRAINT [DF_MT_API_LOGS_TIMESTAMP]  DEFAULT (getdate()) FOR [TIMESTAMP]
GO