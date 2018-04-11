IdCreater = {workId =0, lastTimestamp=0, zipperNum=0}

IdCreater.BIRTH_DAY = 1523342555743
IdCreater.WORKER_ID_BIT = 10
IdCreater.ZIPPER_BIT = 12
IdCreater.DEFAULT_ID = 99999999999999

function IdCreater:new()
    creater = {}
    setmetatable(creater, self)
    self.__index = self
    self.workId = self.getWorkId()
    self.lastTimestamp = self.getTimestamp()
    
    return creater
end

function IdCreater:getId() 
	local timeStamp = self.getTimestamp()

	if timeStamp < self.lastTimestamp
	then
		return IdCreater.DEFAULT_ID
	end

	local maxZipperNumer = math.pow(2, self.ZIPPER_BIT) - 1

	if timeStamp == self.lastTimestamp and maxZipperNumer > self.zipperNum
	then
		self.zipperNum = self.zipperNum + 1
	else
		self.lastTimestamp = self.getNextTimestamp()
		self.zipperNum = 0
	end

	return self:makeId()
end

function IdCreater:makeId()
	local timestampLeftMoveBit = self.ZIPPER_BIT + self.WORKER_ID_BIT
    local workerIdLeftMoveBit = self.WORKER_ID_BIT

    local first  = math.ceil((self.lastTimestamp - IdCreater.BIRTH_DAY) *  math.pow(2, timestampLeftMoveBit))
    local second = math.ceil(self.workId *  math.pow(2, workerIdLeftMoveBit))
    local third  = self.zipperNum

    return string.format("%d", first + second + third)
end

function IdCreater:getNextTimestamp()
    local timestamp = self.getTimestamp()

    while (self.lastTimestamp <= lastTimestamp) 
    do
        timestamp = self.getTimestamp()
    end

    return timestamp
end

function IdCreater:getWorkId()
	return 1
end

function IdCreater:getTimestamp()
	return ngx.now() * 1000
end


function getId()
    idcreater = IdCreater:new(workId);
    return idcreater:getId()
end

function myerrorhandler( err )
   print( "ERROR:", err )
end

status, id = xpcall( getId, myerrorhandler)

if status == false 
then
	return IdCreater.DEFAULT_ID
else
	return id
end





